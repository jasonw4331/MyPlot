<?php
namespace MyPlot;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\level\LevelUnloadEvent;
use pocketmine\event\Listener;
use pocketmine\level\generator\biome\Biome;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;
use pocketmine\level\generator\Generator;
use pocketmine\event\EventPriority;
use pocketmine\plugin\MethodEventExecutor;
use pocketmine\Player;
use pocketmine\block\Block;
use MyPlot\provider\DataProvider;

class MyPlot extends PluginBase implements Listener
{
    private $levels;

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
        $level = $this->getServer()->getLevel($plot->levelName);
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
     *  Reset all the blocks inside a plot
     *
     * @api
     * @param Plot $plot
     * @return bool
     */
    public function resetPlot(Plot $plot) {
        if (!isset($this->levels[$plot->levelName])) {
            return false;
        }
        $levelData = $this->levels[$plot->levelName];

        $level = $this->getServer()->getLevel($plot->levelName);
        $pos1 = $pos2 = $this->getPlotPosition($plot);
        $plotSize = $levelData["PlotSize"];
        $pos2->x += $plotSize;
        $pos2->z += $plotSize;

        $height = $levelData["GroundHeight"];
        $bottomBlock = $levelData["BottomBlock"];
        $plotFillBlock = $levelData["PlotFillBlock"];
        $plotFloorBlock = $levelData["PlotFloorBlock"];
        $air = Block::get(0);
        $pos = new Position(0, 0, 0, $pos1->level);
        for ($x = $pos1->x; $x < $pos2->x; $x++) {
            $pos->x = $x;
            for ($z = $pos1->z; $z < $pos2->z; $z++) {
                $pos->z = $z;
                for ($y = 0; $y < 128; $y++) {
                    $pos->y = $y;
                    if ($y === 0) {
                        $block = $bottomBlock;
                    } elseif ($y < ($height - 1)) {
                        $block = $plotFillBlock;
                    } elseif ($y === ($height - 1)) {
                        $block = $plotFloorBlock;
                    } else {
                        $block = $air;
                    }
                    if ($level->setBlock($pos, $block, false, false) === false) {
                        return false;
                    }
                }
            }
        }
        return true;
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


    /* -------------------------- Non-API part -------------------------- */


    public function onEnable() {
        $folder = $this->getDataFolder();
        if (!is_dir($folder)) {
            mkdir($folder);
        }

        Generator::addGenerator(MyPlotGenerator::class, "myplot");

        $this->saveDefaultConfig();
        $this->reloadConfig();

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
    }

    public function onBlockBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $levelName = $player->getLevel()->getName();
        if (isset($this->levels[$levelName])) {
            $plot = $this->getPlotByPosition($event->getBlock());
            if ($plot !== null) {
                $username = $player->getName();
                if (!($plot->owner === $username or $plot->isHelper($username))) {
                    $event->setCancelled(true);
                    $username->sendMessage(TextFormat::DARK_RED."You cannot break blocks in plots you do not own.");
                }
            }
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event) {
        $player = $event->getPlayer();
        $levelName = $player->getLevel()->getName();
        if (isset($this->levels[$levelName])) {
            $plot = $this->getPlotByPosition($event->getBlock());
            if ($plot !== null) {
                $username = $player->getName();
                if (!($plot->owner === $username or $plot->isHelper($username))) {
                    $event->setCancelled(true);
                    $username->sendMessage(TextFormat::DARK_RED."You cannot place blocks in plots you do not own.");

                }
            }
        }
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
            $requiredKeys = [
                "RoadBlock", "WallBlock", "PlotFloorBlock", "PlotFillBlock",
                "BottomBlock", "RoadWidth", "PlotSize", "GroundHeight"
            ];
            if (count(array_intersect_key(array_flip($requiredKeys), $settings)) === count($requiredKeys)) {
                $this->levels[$event->getLevel()->getName()] = $settings;
            }
        }
    }

    public function onLevelUnload(LevelUnloadEvent $event) {
        $levelName = $event->getLevel()->getName();
        if (isset($this->levels[$levelName])) {
            unset($this->levels[$levelName]);
        }
    }
}
