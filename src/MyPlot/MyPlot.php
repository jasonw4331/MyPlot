<?php
namespace MyPlot;

use MyPlot\task\ClearPlotTask;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\level\LevelUnloadEvent;
use pocketmine\event\Listener;
use pocketmine\level\generator\biome\Biome;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\level\generator\Generator;
use pocketmine\event\EventPriority;
use pocketmine\plugin\MethodEventExecutor;
use pocketmine\Player;
use pocketmine\block\Block;
use MyPlot\provider\DataProvider;
use pocketmine\utils\TextFormat;

class MyPlot extends PluginBase implements Listener
{
    private $levels = array();

    /** @var DataProvider */
    private $provider;


    /**
     * @api
     * @return DataProvider
     */
    public function getProvider() {
        return $this->provider;
    }

    /**
     * @api
     * @param string $levelName
     * @return array
     */
    public function getLevelOptions($levelName) {
        if (isset($this->levels[$levelName])) {
            return $this->levels[$levelName];
        } else {
            return [];
        }
    }

    /**
     * @api
     * @param string $levelName
     * @return bool
     */
    public function isLevelLoaded($levelName) {
        return isset($this->levels[$levelName]);
    }

    /**
     * @api
     * @param string $levelName
     * @return bool
     */
    public function generateLevel($levelName, $options = []) {
        if ($this->getServer()->isLevelGenerated($levelName) === true) {
            return false;
        }
        if (count($options) === 0) {
            $options = $this->getConfig()->get("default_generator");
        }
        $options = [
            "preset" => json_encode($options)
        ];
        return $this->getServer()->generateLevel($levelName, null, MyPlotGenerator::class, $options);
    }

    /**
     * @api
     * @param Position $position
     * @return Plot|null
     */
    public function getPlotByPosition(Position $position) {
        $x = $position->x;
        $z = $position->z;
        $levelName = $position->level->getName();

        if (!isset($this->levels[$levelName])) {
            return null;
        }
        $levelData = $this->levels[$levelName];

        $plotSize = $levelData["PlotSize"];
        $roadWidth = $levelData["RoadWidth"];
        $totalSize = $plotSize + $roadWidth;

        if ($x >= 0) {
            $X = floor($x / $totalSize);
            $difX = $x % $totalSize;
        } else {
            $X = ceil(($x - $plotSize + 1) / $totalSize);
            $difX = abs(($x - $plotSize + 1) % $totalSize);
        }

        if ($z >= 0) {
            $Z = floor($z / $totalSize);
            $difZ = $z % $totalSize;
        } else {
            $Z = ceil(($z - $plotSize + 1) / $totalSize);
            $difZ = abs(($z - $plotSize + 1) % $totalSize);
        }

        if (($difX > $plotSize - 1) or ($difZ > $plotSize - 1)) {
            return null;
        }

        $plot = $this->provider->getPlot($levelName, $X, $Z);
        if ($plot === null) {
            $plot = new Plot($levelName, $X, $Z);
        }
        return $plot;
    }

    /**
     *  Get the begin position of a plot
     *
     * @api
     * @param Plot $plot
     * @return Position|null
     */
    public function getPlotPosition(Plot $plot) {
        if (isset($this->levels[$plot->levelName]) === false) {
            return null;
        }
        $levelData = $this->levels[$plot->levelName];

        $plotSize = $levelData["PlotSize"];
        $roadWidth = $levelData["RoadWidth"];
        $totalSize = $plotSize + $roadWidth;
        if ($plot->X < 0) {
            $x = $totalSize * $plot->X + $roadWidth;
        } else {
            $x = $totalSize * $plot->X;
        }
        if ($plot->Z < 0) {
            $z = $totalSize * $plot->Z + $roadWidth;
        } else {
            $z = $totalSize * $plot->Z;
        }
        $level = $this->getServer()->getLevelByName($plot->levelName);
        return new Position($x, $levelData["GroundHeight"], $z, $level);
    }

    /**
     * @api
     * @param Player $player
     * @param Plot $plot
     * @return bool
     */
    public function teleportPlayerToPlot(Player $player, Plot $plot) {
        if (!isset($this->levels[$plot->levelName])) {
            return false;
        }
        $pos = $this->getPlotPosition($plot);
        $plotSize = $this->levels[$plot->levelName]["PlotSize"];
        $pos->x += floor($plotSize / 2);
        $pos->z += floor($plotSize / 2);
        $player->teleport($pos);
        return true;
    }

    /**
     * Reset all the blocks inside a plot
     *
     * @api
     * @param Plot $plot
     * @param Player $issuer
     * @param int $maxBlocksPerTick
     * @return bool
     */
    public function clearPlot(Plot $plot, Player $issuer = null, $maxBlocksPerTick = 256) {
        if (!isset($this->levels[$plot->levelName])) {
            return false;
        }
        $task = new ClearPlotTask($this, $plot, $issuer, $maxBlocksPerTick);
        $task->onRun(0);
        return true;
    }

    /**
     * Delete the plot data
     *
     * @param Plot $plot
     * @return bool
     */
    public function disposePlot(Plot $plot) {
        return $this->provider->deletePlot($plot);
    }

    /**
     * Clear and dispose a plot
     *
     * @param Plot $plot
     * @return bool
     */
    public function resetPlot(Plot $plot) {
        if ($this->disposePlot($plot)) {
            return $this->clearPlot($plot);
        }
        return false;
    }

    /**
     * @api
     * @param Plot $plot
     * @param Biome $biome
     * @return bool
     */
    public function setPlotBiome(Plot $plot, Biome $biome) {
        if (!isset($this->levels[$plot->levelName])) {
            return false;
        }
        $levelData = $this->levels[$plot->levelName];

        $level = $this->getServer()->getLevel($plot->levelName);
        $pos1 = $pos2 = $this->getPlotPosition($plot);
        $plotSize = $levelData["PlotSize"];
        $pos2->x += $plotSize;
        $pos2->z += $plotSize;

        for ($x = $pos1->x; $x < $pos2->x; $x++) {
            for ($z = $pos1->z; $z < $pos2->z; $z++) {
                $level->setBiomeId($x, $z, $biome->getId());
                $color = $biome->getColor();
                $R = $color >> 16;
                $G = ($color >> 8) & 0xff;
                $B = $color & 0xff;
                $level->setBiomeColor($x, $z, $R, $G, $B);
            }
        }
        return true;
    }

    /**
     * @return string[]
     */
    public function getPlotLevels() {
        return array_keys($this->levels);
    }


    /* -------------------------- Non-API part -------------------------- */


    public function onEnable() {
        $folder = $this->getDataFolder();
        if (!is_dir($folder)) {
            mkdir($folder);
        }

        Generator::addGenerator(MyPlotGenerator::class, "myplot");

        $this->saveDefaultConfig();
        $this->reloadConfig();
        $this->getLogger()->info(TextFormat::GREEN."Loading the Plot Framework!");
        $this->getLogger()->warning(TextFormat::YELLOW."It seems that you are running the development build of MyPlot! Thats cool, but it CAN be very, very buggy! Just be careful when using this plugin and report any issues to".TextFormat::GOLD." http://github.com/wiez/MyPlot/issues");

        $pluginManager = $this->getServer()->getPluginManager();
        $pluginManager->registerEvent("pocketmine\\event\\block\\BlockBreakEvent", $this, EventPriority::HIGH, new MethodEventExecutor("onBlockBreak"), $this, false);
        $pluginManager->registerEvent("pocketmine\\event\\block\\BlockPlaceEvent", $this, EventPriority::HIGH, new MethodEventExecutor("onBlockPlace"), $this, false);
        $pluginManager->registerEvent("pocketmine\\event\\level\\LevelLoadEvent", $this, EventPriority::HIGH, new MethodEventExecutor("onLevelLoad"), $this, false);
        $pluginManager->registerEvent("pocketmine\\event\\level\\LevelUnloadEvent", $this, EventPriority::HIGH, new MethodEventExecutor("onLevelUnload"), $this, false);
        $this->getServer()->getCommandMap()->register(Commands::class, new Commands($this));

        switch (strtolower($this->getConfig()->get("data_provider"))) {
            case "sqlite":
                $this->provider = new provider\SQLiteDataProvider($this);
                break;
            default:
                $this->provider = new provider\SQLiteDataProvider($this);
        }
    }

    public function onDisable() {
        $this->provider->close();
        $this->getLogger()->info(TextFormat::GREEN."Saving plots");
        $this->getLogger()->info(TextFormat::BLUE."Disabled the plot framework!");
    }

    public function onBlockBreak(BlockBreakEvent $event) {
        $levelName = $event->getPlayer()->getLevel()->getName();
        if (!isset($this->levels[$levelName])) {
            return;
        }
        $plot = $this->getPlotByPosition($event->getBlock());
        if ($plot !== null) {
            $username = $event->getPlayer()->getName();
            if ($plot->owner == $username or $plot->isHelper($username)) {
                return;
            }
        }
        $event->setCancelled(true);
    }

    public function onBlockPlace(BlockPlaceEvent $event) {
        $levelName = $event->getPlayer()->getLevel()->getName();
        if (!isset($this->levels[$levelName])) {
            return;
        }
        $plot = $this->getPlotByPosition($event->getBlock());
        if ($plot !== null) {
            $username = $event->getPlayer()->getName();
            if ($plot->owner == $username or $plot->isHelper($username)) {
                return;
            }
        }
        $event->setCancelled(true);
    }

    public function onLevelLoad(LevelLoadEvent $event) {
        if ($event->getLevel()->getProvider()->getGenerator() === "myplot") {
            $settings = $event->getLevel()->getProvider()->getGeneratorOptions();
            if (isset($settings["preset"]) === false or $settings["preset"] === "") {
                return;
            }
            $settings = json_decode($settings["preset"], true);
            if ($settings === false) {
                return;
            }
            $this->levels[$event->getLevel()->getName()] = [
                "RoadBlock" => $this->parseBlock($settings, "RoadBlock", new Block(5)),
                "WallBlock" => $this->parseBlock($settings, "WallBlock", new Block(44)),
                "PlotFloorBlock" => $this->parseBlock($settings, "PlotFloorBlock", new Block(2)),
                "PlotFillBlock" => $this->parseBlock($settings, "PlotFillBlock", new Block(3)),
                "BottomBlock" => $this->parseBlock($settings, "BottomBlock", new Block(7)),
                "RoadWidth" => $this->parseNumber($settings, "RoadWidth", 7),
                "PlotSize" => $this->parseNumber($settings, "PlotSize", 22),
                "GroundHeight" => $this->parseNumber($settings, "GroundHeight", 64),
            ];
        }
    }

    public function onLevelUnload(LevelUnloadEvent $event) {
        $levelName = $event->getLevel()->getName();
        if (isset($this->levels[$levelName])) {
            unset($this->levels[$levelName]);
        }
    }

    private function parseBlock($array, $key, $default) {
        if (isset($array[$key])) {
            $id = $array[$key];
            if (is_numeric($id)) {
                $block = new Block($id);
            } else {
                $split = explode(":", $id);
                if (count($split) === 2 and is_numeric($split[0]) and is_numeric($split[1])) {
                    $block = new Block($split[0], $split[1]);
                } else {
                    $block = $default;
                }
            }
        } else {
            $block = $default;
        }
        return $block;
    }

    private function parseNumber($array, $key, $default) {
        if (isset($array[$key]) and is_numeric($array[$key])) {
            return $array[$key];
        } else {
            return $default;
        }
    }
}
