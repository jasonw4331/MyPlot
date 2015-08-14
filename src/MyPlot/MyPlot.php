<?php
namespace MyPlot;

use MyPlot\provider\EconomySProvider;
use MyPlot\provider\PocketMoneyProvider;
use MyPlot\task\ClearPlotTask;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockUpdateEvent;
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
use MyPlot\provider\DataProvider;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\level\Level;
use MyPlot\provider\SQLiteDataProvider;
use MyPlot\provider\EconomyProvider;

class MyPlot extends PluginBase implements Listener
{
    /** @var MyPlot */
    private static $instance;
    /** @var PlotLevelSettings[] */
    private $levels = [];
    /** @var DataProvider */
    private $dataProvider;
    /** @var EconomyProvider */
    private $economyProvider;

    /**
     * @api
     * @return MyPlot
     */
    public static function getInstance(){
        return self::$instance;
    }

    /**
     * Returns the DataProvider that is being used
     *
     * @api
     * @return DataProvider
     */
    public function getProvider() {
        return $this->dataProvider;
    }

    /**
     * Returns the EconomyProvider that is being used
     *
     * @api
     * @return EconomyProvider
     */
    public function getEconomyProvider() {
        return $this->economyProvider;
    }

    /**
     * Returns a PlotLevelSettings object which contains all the settings of a level
     *
     * @api
     * @param string $levelName
     * @return PlotLevelSettings|null
     */
    public function getLevelSettings($levelName) {
        if (isset($this->levels[$levelName])) {
            return $this->levels[$levelName];
        }
        return null;
    }

    /**
     * Checks if a plot level is loaded
     *
     * @api
     * @param string $levelName
     * @return bool
     */
    public function isLevelLoaded($levelName) {
        return isset($this->levels[$levelName]);
    }

    /**
     * Generate a new plot level with optional settings
     *
     * @api
     * @param string $levelName
     * @param array $settings
     * @return bool
     */
    public function generateLevel($levelName, $settings = []) {
        if ($this->getServer()->isLevelGenerated($levelName) === true) {
            return false;
        }
        if (empty($settings)) {
            $settings = $this->getConfig()->get("DefaultWorld");
        }
        $settings = [
            "preset" => json_encode($settings)
        ];
        return $this->getServer()->generateLevel($levelName, null, MyPlotGenerator::class, $settings);
    }

    /**
     * Saves provided plot if changed
     *
     * @api
     * @param Plot $plot
     * @return bool
     */
    public function savePlot(Plot $plot) {
        return $this->dataProvider->savePlot($plot);
    }

    /**
     * Get all the plots a player owns (in a certain level if $levelName is provided)
     *
     * @api
     * @param string $username
     * @param string $levelName
     * @return Plot[]
     */
    public function getPlotsOfPlayer($username, $levelName = "") {
        return $this->dataProvider->getPlotsByOwner($username, $levelName);
    }

    /**
     * Get the next free plot in a level
     *
     * @api
     * @param string $levelName
     * @param int $limitXZ
     * @return Plot|null
     */
    public function getNextFreePlot($levelName, $limitXZ = 20) {
        return $this->dataProvider->getNextFreePlot($levelName, $limitXZ);
    }

    /**
     * Finds the plot at a certain position or null if there is no plot at that position
     *
     * @api
     * @param Position $position
     * @return Plot|null
     */
    public function getPlotByPosition(Position $position) {
        $x = $position->x;
        $z = $position->z;
        $levelName = $position->level->getName();

        $plotLevel = $this->getLevelSettings($levelName);
        if ($plotLevel === null) {
            return null;
        }

        $plotSize = $plotLevel->plotSize;
        $roadWidth = $plotLevel->roadWidth;
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

        return $this->dataProvider->getPlot($levelName, $X, $Z);
    }

    /**
     *  Get the begin position of a plot
     *
     * @api
     * @param Plot $plot
     * @return Position|null
     */
    public function getPlotPosition(Plot $plot) {
        $plotLevel = $this->getLevelSettings($plot->levelName);
        if ($plotLevel === null) {
            return null;
        }

        $plotSize = $plotLevel->plotSize;
        $roadWidth = $plotLevel->roadWidth;
        $totalSize = $plotSize + $roadWidth;
        $x = $totalSize * $plot->X;
        $z = $totalSize * $plot->Z;
        $level = $this->getServer()->getLevelByName($plot->levelName);
        return new Position($x, $plotLevel->groundHeight, $z, $level);
    }

    /**
     * Teleport a player to a plot
     *
     * @api
     * @param Player $player
     * @param Plot $plot
     * @return bool
     */
    public function teleportPlayerToPlot(Player $player, Plot $plot) {
        $plotLevel = $this->getLevelSettings($plot->levelName);
        if ($plotLevel === null) {
            return false;
        }
        $pos = $this->getPlotPosition($plot);
        $plotSize = $plotLevel->plotSize;
        $pos->x += floor($plotSize / 2);
        $pos->z -= 1;
        $pos->y += 1;
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
        if (!$this->isLevelLoaded($plot->levelName)) {
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
        return $this->dataProvider->deletePlot($plot);
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
     * Changes the biome of a plot
     *
     * @api
     * @param Plot $plot
     * @param Biome $biome
     * @return bool
     */
    public function setPlotBiome(Plot $plot, Biome $biome) {
        $plotLevel = $this->getLevelSettings($plot->levelName);
        if ($plotLevel === null) {
            return false;
        }

        $level = $this->getServer()->getLevelByName($plot->levelName);
        $pos = $this->getPlotPosition($plot);
        $plotSize = $plotLevel->plotSize;
        $xMax = $pos->x + $plotSize;
        $zMax = $pos->z + $plotSize;

        $chunkIndexes = [];
        for ($x = $pos->x; $x < $xMax; $x++) {
            for ($z = $pos->z; $z < $zMax; $z++) {
                $index = Level::chunkHash($x >> 4, $z >> 4);
                if (!in_array($index, $chunkIndexes)) {
                    $chunkIndexes[] = $index;
                }
                $color = $biome->getColor();
                $R = $color >> 16;
                $G = ($color >> 8) & 0xff;
                $B = $color & 0xff;
                $level->setBiomeColor($x, $z, $R, $G, $B);
            }
        }

        foreach ($chunkIndexes as $index) {
            Level::getXZ($index, $X, $Z);
            $chunk = $level->getChunk($X, $Z);
            foreach ($level->getChunkPlayers($X, $Z) as $player) {
                $player->onChunkChanged($chunk);
            }
        }

        $plot->biome = $biome->getName();
        $this->dataProvider->savePlot($plot);
        return true;
    }

    /**
     * Returns the PlotLevelSettings of all the loaded levels
     *
     * @api
     * @return string[]
     */
    public function getPlotLevels() {
        return $this->levels;
    }


    /* -------------------------- Non-API part -------------------------- */


    public function onEnable() {
        self::$instance = $this;

        $folder = $this->getDataFolder();
        if (!is_dir($folder)) {
            mkdir($folder);
        }
        if (!is_dir($folder . "worlds")) {
            mkdir($folder . "worlds");
        }

        Generator::addGenerator(MyPlotGenerator::class, "myplot");

        $this->saveDefaultConfig();
        $this->reloadConfig();
        $this->getLogger()->info(TextFormat::GREEN."Loading the Plot Framework!");
        $this->getLogger()->warning(TextFormat::YELLOW."It seems that you are running the development build of MyPlot! Thats cool, but it CAN be very, very buggy! Just be careful when using this plugin and report any issues to".TextFormat::GOLD." http://github.com/wiez/MyPlot/issues");

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->register(Commands::class, new Commands($this));

        $cacheSize = $this->getConfig()->get("PlotCacheSize");
        switch (strtolower($this->getConfig()->get("DataProvider"))) {
            case "sqlite":
            default:
                $this->dataProvider = new SQLiteDataProvider($this, $cacheSize);
                break;
        }

        if ($this->getConfig()->get("UseEconomy") == true) {
            if ($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") !== null) {
                $this->economyProvider = new EconomySProvider();
            } elseif (($plugin = $this->getServer()->getPluginManager()->getPlugin("PocketMoney")) !== null) {
                $this->economyProvider = new PocketMoneyProvider($plugin);
            } else {
                $this->economyProvider = null;
            }
        } else {
            $this->economyProvider = null;
        }
    }

    public function addLevelSettings($levelName, PlotLevelSettings $settings) {
        $this->levels[$levelName] = $settings;
    }

    public function unloadLevelSettings($levelName) {
        if (isset($this->levels[$levelName])) {
            unset($this->levels[$levelName]);
            return true;
        }
        return false;
    }

    public function onDisable() {
        $this->dataProvider->close();
        $this->getLogger()->info(TextFormat::GREEN."Saving plots");
        $this->getLogger()->info(TextFormat::BLUE."Disabled the plot framework!");
    }
}
