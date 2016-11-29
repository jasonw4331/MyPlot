<?php
namespace MyPlot;

use MyPlot\provider\EconomySProvider;
use MyPlot\provider\PocketMoneyProvider;
use MyPlot\task\ClearPlotTask;
use MyPlot\provider\DataProvider;
use MyPlot\provider\SQLiteDataProvider;
use MyPlot\provider\EconomyProvider;

use pocketmine\block\Air;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\lang\BaseLang;
use pocketmine\level\generator\biome\Biome;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\permission\Permission;
use pocketmine\plugin\PluginBase;
use pocketmine\level\generator\Generator;
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\utils\TextFormat;

class MyPlot extends PluginBase
{

    /** @var PlotLevelSettings[] */
    private $levels = [];

	/** @var [] */
	private $generators = [];

    /** @var DataProvider */
    private $dataProvider = null;

    /** @var EconomyProvider */
    private $economyProvider = null;

    /** @var BaseLang */
    private $baseLang = null;

	CONST GENERATOR_API = "1.1.0";

    /**
     * @api
     * @return BaseLang
     */
    public function getLanguage() {
        return $this->baseLang;
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
     * @param $levelName
     * @return Plot[]
     */
    public function getPlotsOfPlayer($username, $levelName) {
	      if($levelName instanceof Level) {
		        $levelName = $levelName->getName();
	      }
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
    public function getNextFreePlot($levelName, $limitXZ = 0) {
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
        }else{
            $X = ceil(($x - $plotSize + 1) / $totalSize);
            $difX = abs(($x - $plotSize + 1) % $totalSize);
        }

        if ($z >= 0) {
            $Z = floor($z / $totalSize);
            $difZ = $z % $totalSize;
        }else{
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
     * @param int $maxBlocksPerTick
     * @return bool
     */
    public function clearPlot(Plot $plot, $maxBlocksPerTick = 256) {
        if (!$this->isLevelLoaded($plot->levelName)) {
            return false;
        }
        foreach($this->getServer()->getLevelByName($plot->levelName)->getEntities() as $entity) {
            $plotb = $this->getPlotByPosition($entity->getPosition());
            if($plotb != null) {
                if($plotb === $plot) {
                    if(!$entity instanceof Player) {
                        $entity->close();
                    }
                }
            }
        }
        $this->getServer()->getScheduler()->scheduleTask(new ClearPlotTask($this, $plot, $maxBlocksPerTick));
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
     * @param int $maxBlocksPerTick
     * @return bool
     */
    public function resetPlot(Plot $plot, $maxBlocksPerTick = 256) {
        if ($this->disposePlot($plot)) {
            return $this->clearPlot($plot, $maxBlocksPerTick);
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
            Level::getXZ($index, $plot->X, $plot->Z);
            $chunk = $level->getChunk($plot->X,$plot->Z);
            foreach ($level->getChunkPlayers($plot->X, $plot->Z) as $player) {
                $player->onChunkChanged($chunk);
            }
        }

        $plot->biome = $biome->getName();
        $this->savePlot($plot);
        return true;
    }

    /**
     * Returns the PlotLevelSettings of all the loaded levels
     *
     * @api
     * @return PlotLevelSettings[]
     */
    public function getPlotLevels() {
        return $this->levels;
    }

    /**
     * Get the maximum number of plots a player can claim
     *
     * @param Player $player
     * @return int
     */
    public function getMaxPlotsOfPlayer(Player $player) {
        if ($player->hasPermission("myplot.claimplots.unlimited"))
            return PHP_INT_MAX;
        /** @var Permission[] $perms */
        $perms = array_merge($this->getServer()->getPluginManager()->getDefaultPermissions($player->isOp()),
            $player->getEffectivePermissions());
        $perms = array_filter($perms, function ($name) {
            return (substr($name, 0, 18) === "myplot.claimplots.");
        }, ARRAY_FILTER_USE_KEY);
        if (count($perms) == 0)
            return 0;
        krsort($perms);
        foreach ($perms as $name => $perm) {
            $maxPlots = substr($name, 18);
            if (is_numeric($maxPlots)) {
                return $maxPlots;
            }
        }
        return 0;
    }

    /**
     * Finds the exact center of the plot at ground level
     *
     * @param Plot $plot
     * @return Vector3|bool
     */
    public function getPlotMid(Plot $plot) {
        $gh = $this->getLevelSettings($plot->levelName)->groundHeight;
        $ps = $this->getLevelSettings($plot->levelName)->plotSize;
        $rw = $this->getLevelSettings($plot->levelName)->roadWidth;
        $totalSize = $ps + $rw;
        $x = $plot->X;
        $z = $plot->Z;
        if ($x >= 0) {
            if($h = $ps % 2 == 0) {
                $X = floor($x / $totalSize) + $h;
            }else{
                $X = floor($x / $totalSize) + ($ps / 2) + 0.5;
            }
        }else{
            if($h = $ps % 2 == 0) {
                $X = ceil(($x - $ps + 1) / $totalSize) - $h;
            }else{
                $X = ceil(($x - $ps + 1) / $totalSize) - ($ps / 2) - 0.5;
            }
        }
        if ($z >= 0) {
            if($h = $ps % 2 == 0) {
                $Z = floor($z / $totalSize) + $h;
            }else{
                $Z = floor($z / $totalSize) + ceil($ps / 2) + 0.5;
            }
        }else{
            if ($h = $ps % 2 == 0) {
                $Z = floor($z / $totalSize) - $h;
            }else{
                $Z = floor($z / $totalSize) - ceil($ps / 2) - 0.5;
            }
        }
        for($Y = $gh; $h<128; $h++) {
            $mid = new Vector3($X,$Y+0.5,$Z);
            $mida = new Vector3($X,$Y,$Z);
            $midb = new Vector3($X,$Y+1,$Z);
            if ($this->getServer()->getLevelByName($plot->levelName)->getBlock($mida) === Air::class and $this->getServer()->getLevelByName($plot->levelName)->getBlock($midb) === Air::class) {
                return $mid;
            }
        }
        return false;
    }

    /**
     * Teleports the player to the exact center of the plot at nearest open space to the ground level
     *
     * @param Plot $plot
     * @param Player $player
     * @return bool
     */
    public function teleportMiddle(Plot $plot, Player $player) {
        $mid = $this->getPlotMid($plot);
        if($mid === false) {
            $this->teleportPlayerToPlot($player, $plot);
        }
        $player->teleport($mid);
        return true;
    }

    /* -------------------------- Non-API part -------------------------- */

    public function onEnable() {
        $this->getLogger()->info("Loading MyPlot");

        $this->saveDefaultConfig();
        $this->reloadConfig();

        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder() . "worlds");

	    $dir = dir($this->getDataFolder());
	    while(false !== ($file = $dir->read())) {
		    if($file{0} !== ".") {
			    $ext = strtolower(substr($file, -3));
			    if($ext === "php") {
				    $this->loadGenerator($dir->path . $file);
			    }
		    }
	    }

	    $this->saveResource("MyPlotGenerator.php");

        $gen = strtolower($this->getConfig()->get("Generator","MyPlotGenerator"));
        if($gen == "myplotgenerator" or $gen== "default") {
                Generator::addGenerator(MyPlotGenerator::class, "myplot");
        }else{
                Generator::addGenerator(MyPlotGenerator::class, "myplot");
        }

        $lang = $this->getConfig()->get("language", BaseLang::FALLBACK_LANGUAGE);
        $this->baseLang = new BaseLang($lang, $this->getFile() . "resources/");

        // Initialize DataProvider
        $cacheSize = $this->getConfig()->get("PlotCacheSize");
        switch (strtolower($this->getConfig()->get("DataProvider"))) {
            case "mysql":
                $settings = $this->getConfig()->get("MySQLSettings");
                $this->dataProvider = new MySQLProvider($this, $cacheSize, $settings);
            break;
            case "json":
                $this->dataProvider = new JSONDataProvider($this, $cacheSize);
            break;
            case "sqlite3":
            case "sqlite":
            default:
                $this->dataProvider = new SQLiteDataProvider($this, $cacheSize);
            break;
        }

        // Initialize EconomyProvider
        if ($this->getConfig()->get("UseEconomy") == true) {
            if ($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") !== null) {
                $this->economyProvider = new EconomySProvider();
            } elseif (($plugin = $this->getServer()->getPluginManager()->getPlugin("PocketMoney")) !== null) {
                $this->economyProvider = new PocketMoneyProvider($plugin);
            }
        }

        $eventListener = new EventListener($this);
        $this->getServer()->getPluginManager()->registerEvents($eventListener, $this);
        foreach($this->getServer()->getLevels() as $level) {
            $eventListener->onLevelLoad(new LevelLoadEvent($level));
        }
        $this->getServer()->getCommandMap()->register(Commands::class, new Commands($this));
    }

	/**
	 * @param string $file
	 * @return bool
	 */
	public function loadGenerator($file) {
		if(is_link($file) or is_dir($file) or !file_exists($file)) {
			$this->getLogger()->debug(basename($file)." is not a file");
			return false;
		}
			$content = file_get_contents($file);
			$info = strstr($content, "*/", true);
			$content = str_repeat(PHP_EOL, substr_count($info, "\n")).substr(strstr($content, "*/"),2);
			if(preg_match_all('#([a-zA-Z0-9\-_]*)=([^\r\n]*)#u', $info, $matches) == 0) {
				$this->getLogger()->debug("Failed to parse ".basename($file));
				return false;
			}
			$info = array();
			foreach($matches[1] as $k => $i) {
				$v = $matches[2][$k];
				switch(strtolower($v)) {
					case "on":
					case "true":
					case "yes":
						$v = true;
						break;
					case "off":
					case "false":
					case "no":
						$v = false;
						break;
				}
				$info[$i] = $v;
			}
			$info["code"] = $content;
			$info["class"] = trim(strtolower($info["class"]));
		if(!isset($info["class"]) or !isset($info["version"])) {
			$this->getLogger()->debug("[ERROR] Failed parsing of ".basename($file));
			return false;
		}
		$this->getLogger()->debug("Loading generator \"".TextFormat::GREEN.$info["class"].TextFormat::RESET."\"_v".TextFormat::AQUA.$info["version"].TextFormat::RESET." by ".TextFormat::AQUA.$info["author"].TextFormat::RESET);
		if($info["class"] !== "none" and $info["class"] !== null and class_exists($info["class"])) {
			$this->getLogger()->debug("Failed loading generator: class already exists");
			return false;
		}

		$className = $info["class"];
		$apiversion = array_map("intval", explode(",", $info["version"]));
		if(!in_array(self::GENERATOR_API, $apiversion)) {
			$this->getLogger()->debug("Generator \"".$info["name"]."\" may be outdated!");
		}

		if($info["class"] !== "none" or $info["class"] !== null) {
			$object = new $className([]);
			if(!($object instanceof GeneratorTemplate)) {
				$this->getLogger()->debug("Generator \"".$info["class"]."\" doesn't use the Generator Template");
				if(method_exists($object, "__destruct")) {
					$object->__destruct();
				}
				$object = null;
				unset($object);
			}else{
				$this->generators[] = array($object, $info);
			}
		}
		return true;
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
        if ($this->dataProvider !== null) {
            $this->dataProvider->close();
        }
    }
}