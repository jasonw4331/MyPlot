<?php
declare(strict_types=1);
namespace MyPlot;

use EssentialsPE\Loader;
use muqsit\worldstyler\shapes\Cuboid;
use muqsit\worldstyler\WorldStyler;
use MyPlot\events\MyPlotClearEvent;
use MyPlot\events\MyPlotDisposeEvent;
use MyPlot\events\MyPlotGenerationEvent;
use MyPlot\events\MyPlotResetEvent;
use MyPlot\events\MyPlotSettingEvent;
use MyPlot\events\MyPlotTeleportEvent;
use MyPlot\provider\DataProvider;
use MyPlot\provider\EconomyProvider;
use MyPlot\provider\EconomySProvider;
use MyPlot\provider\EssentialsPEProvider;
use MyPlot\provider\JSONDataProvider;
use MyPlot\provider\MySQLProvider;
use MyPlot\provider\PocketMoneyProvider;
use MyPlot\provider\SQLiteDataProvider;
use MyPlot\provider\YAMLDataProvider;
use MyPlot\task\ClearPlotTask;
use onebone\economyapi\EconomyAPI;
use pocketmine\block\Block;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\lang\BaseLang;
use pocketmine\level\biome\Biome;
use pocketmine\level\format\Chunk;
use pocketmine\level\generator\GeneratorManager;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use PocketMoney\PocketMoney;
use spoondetector\SpoonDetector;

class MyPlot extends PluginBase
{
	/** @var MyPlot $instance */
	private static $instance;
	/** @var PlotLevelSettings[] $levels */
	private $levels = [];
	/** @var DataProvider $dataProvider */
	private $dataProvider = null;
	/** @var EconomyProvider $economyProvider */
	private $economyProvider = null;
	/** @var BaseLang $baseLang */
	private $baseLang = null;

	/**
	 * @return MyPlot
	 */
	public static function getInstance() : self {
		return self::$instance;
	}

	/**
	 * Returns the Multi-lang management class
	 *
	 * @api
	 *
	 * @return BaseLang
	 */
	public function getLanguage() : BaseLang {
		return $this->baseLang;
	}

	/**
	 * Returns the DataProvider that is being used
	 *
	 * @api
	 *
	 * @return DataProvider
	 */
	public function getProvider() : DataProvider {
		return $this->dataProvider;
	}

	/**
	 * Returns the EconomyProvider that is being used
	 *
	 * @api
	 *
	 * @return EconomyProvider|null
	 */
	public function getEconomyProvider() : ?EconomyProvider {
		return $this->economyProvider;
	}

	/**
	 * Allows setting the economy provider to a custom provider or to null to disable economy mode
	 *
	 * @api
	 *
	 * @param EconomyProvider|null $provider
	 */
	public function setEconomyProvider(?EconomyProvider $provider) : void {
		if($provider === null) {
			$this->getConfig()->set("UseEconomy", false);
			$this->getLogger()->info("Economy mode disabled!");
		}else{
			$this->getLogger()->info("A custom economy provider has been registered. Economy mode now enabled!");
			$this->getConfig()->set("UseEconomy", true);
			$this->economyProvider = $provider;
		}
	}

	/**
	 * Returns a PlotLevelSettings object which contains all the settings of a level
	 *
	 * @api
	 *
	 * @param string $levelName
	 *
	 * @return PlotLevelSettings|null
	 */
	public function getLevelSettings(string $levelName) : ?PlotLevelSettings {
		return $this->levels[$levelName] ?? null;
	}

	/**
	 * Checks if a plot level is loaded
	 *
	 * @api
	 *
	 * @param string $levelName
	 *
	 * @return bool
	 */
	public function isLevelLoaded(string $levelName) : bool {
		return isset($this->levels[$levelName]);
	}

	/**
	 * Generate a new plot level with optional settings
	 *
	 * @api
	 *
	 * @param string $levelName
	 * @param string $generator
	 * @param array $settings
	 *
	 * @return bool
	 */
	public function generateLevel(string $levelName, string $generator = "myplot", array $settings = []) : bool {
		$ev = new MyPlotGenerationEvent($levelName, $generator, $settings);
		$ev->call();
		if($ev->isCancelled() or $this->getServer()->isLevelGenerated($levelName)) {
			return false;
		}
		$generator = GeneratorManager::getGenerator($generator);
		if(empty($settings)) {
			$this->getConfig()->reload();
			$settings = $this->getConfig()->get("DefaultWorld", []);
		}
		$default = array_filter($this->getConfig()->get("DefaultWorld", []), function($key){
			return !in_array($key, ["PlotSize", "GroundHeight", "RoadWidth", "RoadBlock", "WallBlock", "PlotFloorBlock", "PlotFillBlock", "BottomBlock"]);
		}, ARRAY_FILTER_USE_KEY);
		new Config($this->getDataFolder()."worlds".DIRECTORY_SEPARATOR.$levelName.".yml", Config::YAML, $default);
		$settings = ["preset" => json_encode($settings)];
		$return = $this->getServer()->generateLevel($levelName, null, $generator, $settings);
		$level = $this->getServer()->getLevelByName($levelName);
		if($level !== null)
			$level->setSpawnLocation(new Vector3(0,(int)$this->getConfig()->getNested("DefaultWorld.GroundHeight", 64) + 1,0));
		return $return;
	}

	/**
	 * Saves provided plot if changed
	 *
	 * @api
	 *
	 * @param Plot $plot
	 *
	 * @return bool
	 */
	public function savePlot(Plot $plot) : bool {
		return $this->dataProvider->savePlot($plot);
	}

	/**
	 * Get all the plots a player owns (in a certain level if $levelName is provided)
	 *
	 * @api
	 *
	 * @param string $username
	 * @param string $levelName
	 *
	 * @return Plot[]
	 */
	public function getPlotsOfPlayer(string $username, string $levelName) : array {
		return $this->dataProvider->getPlotsByOwner($username, $levelName);
	}

	/**
	 * Get the next free plot in a level
	 *
	 * @api
	 *
	 * @param string $levelName
	 * @param int $limitXZ
	 *
	 * @return Plot|null
	 */
	public function getNextFreePlot(string $levelName, int $limitXZ = 0) : ?Plot {
		return $this->dataProvider->getNextFreePlot($levelName, $limitXZ);
	}

	/**
	 * Finds the plot at a certain position or null if there is no plot at that position
	 *
	 * @api
	 *
	 * @param Position $position
	 *
	 * @return Plot|null
	 */
	public function getPlotByPosition(Position $position) : ?Plot {
		$x = $position->x;
		$z = $position->z;
		$levelName = $position->level->getFolderName();

		$plotLevel = $this->getLevelSettings($levelName);
		if($plotLevel === null)
			return null;
		$plotSize = $plotLevel->plotSize;
		$roadWidth = $plotLevel->roadWidth;
		$totalSize = $plotSize + $roadWidth;
		if($x >= 0) {
			$X = (int) floor($x / $totalSize);
			$difX = $x % $totalSize;
		}else{
			$X = (int) ceil(($x - $plotSize + 1) / $totalSize);
			$difX = abs(($x - $plotSize + 1) % $totalSize);
		}
		if($z >= 0) {
			$Z = (int) floor($z / $totalSize);
			$difZ = $z % $totalSize;
		}else{
			$Z = (int) ceil(($z - $plotSize + 1) / $totalSize);
			$difZ = abs(($z - $plotSize + 1) % $totalSize);
		}
		if(($difX > $plotSize - 1) or ($difZ > $plotSize - 1)) {
			return null;
		}
		return $this->dataProvider->getPlot($levelName, $X, $Z);
	}

	/**
	 * Get the begin position of a plot
	 *
	 * @api
	 *
	 * @param Plot $plot
	 *
	 * @return Position|null
	 */
	public function getPlotPosition(Plot $plot) : ?Position {
		$plotLevel = $this->getLevelSettings($plot->levelName);
		if($plotLevel === null)
			return null;
		$plotSize = $plotLevel->plotSize;
		$roadWidth = $plotLevel->roadWidth;
		$totalSize = $plotSize + $roadWidth;
		$x = $totalSize * $plot->X;
		$z = $totalSize * $plot->Z;
		$level = $this->getServer()->getLevelByName($plot->levelName);
		return new Position($x, $plotLevel->groundHeight, $z, $level);
	}

	/**
	 * Returns the AABB of the plot area
	 *
	 * @api
	 *
	 * @param Plot $plot
	 *
	 * @return AxisAlignedBB|null
	 */
	public function getPlotBB(Plot $plot) : ?AxisAlignedBB {
		$plotLevel = $this->getLevelSettings($plot->levelName);
		if($plotLevel === null)
			return null;
		$pos = $this->getPlotPosition($plot);
		$plotSize = $plotLevel->plotSize-1;

		return new AxisAlignedBB(
			min($pos->x, $pos->x + $plotSize),
			0,
			min($pos->z, $pos->z + $plotSize),
			max($pos->x, $pos->x + $plotSize),
			$pos->getLevel()->getWorldHeight(),
			max($pos->z, $pos->z + $plotSize)
		);
	}

	/**
	 * Teleport a player to a plot
	 *
	 * @api
	 *
	 * @param Player $player
	 * @param Plot $plot
	 * @param bool $center
	 *
	 * @return bool
	 */
	public function teleportPlayerToPlot(Player $player, Plot $plot, bool $center = false) : bool {
		$ev = new MyPlotTeleportEvent($plot, $player, $center);
		$ev->call();
		if($ev->isCancelled()) {
			return false;
		}
		if($center)
			return $this->teleportMiddle($player, $plot);
		$plotLevel = $this->getLevelSettings($plot->levelName);
		if($plotLevel === null)
			return false;
		$pos = $this->getPlotPosition($plot);
		$pos->x += floor($plotLevel->plotSize / 2);
		$pos->y += 1.5;
		$pos->z -= 1;
		return $player->teleport($pos);
	}

	/**
	 * Claims a plot in a players name
	 *
	 * @api
	 *
	 * @param Plot $plot
	 * @param string $claimer
	 * @param string $plotName
	 *
	 * @return bool
	 */
	public function claimPlot(Plot $plot, string $claimer, string $plotName = "") : bool {
		$newPlot = clone $plot;
		$newPlot->owner = $claimer;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$ev->call();
		if($ev->isCancelled()) {
			return false;
		}
		$plot = $ev->getPlot();
		if(!empty($plotName)) {
			$this->renamePlot($plot, $plotName);
		}
		return $this->savePlot($plot);
	}

	/**
	 * @param Plot $plot
	 * @param string $newName
	 *
	 * @return bool
	 */
	public function renamePlot(Plot $plot, string $newName = "") : bool {
		$newPlot = clone $plot;
		$newPlot->name = $newName;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$ev->call();
		if($ev->isCancelled()) {
			return false;
		}
		return $this->savePlot($ev->getPlot());
	}

	/**
	 * Reset all the blocks inside a plot
	 *
	 * @api
	 *
	 * @param Plot $plot
	 * @param int $maxBlocksPerTick
	 *
	 * @return bool
	 */
	public function clearPlot(Plot $plot, int $maxBlocksPerTick = 256) : bool {
		if(!$this->isLevelLoaded($plot->levelName)) {
			return false;
		}
		$ev = new MyPlotClearEvent($plot, $maxBlocksPerTick);
		$ev->call();
		if($ev->isCancelled()) {
			return false;
		}
		$plot = $ev->getPlot();
		$maxBlocksPerTick = $ev->getMaxBlocksPerTick();
		foreach($this->getServer()->getLevelByName($plot->levelName)->getEntities() as $entity) {
			if($this->getPlotBB($plot)->isVectorInXZ($entity)) {
				if(!$entity instanceof Player) {
					$entity->flagForDespawn();
				}else{
					$this->teleportPlayerToPlot($entity, $plot);
				}
			}
		}
		if($this->getConfig()->get("FastClearing", false)) {
			/** @var WorldStyler $styler */
			$styler = $this->getServer()->getPluginManager()->getPlugin("WorldStyler");
			$plotLevel = $this->getLevelSettings($plot->levelName);
			$plotSize = $plotLevel->plotSize-1;
			$plotBeginPos = $this->getPlotPosition($plot);
			$plugin = $this;
			// Above ground
			$selection = $styler->getSelection(99998);
			$plotBeginPos->y = $plotLevel->groundHeight+1;
			$selection->setPosition(1, $plotBeginPos);
			$selection->setPosition(2, new Vector3($plotBeginPos->x + $plotSize, Level::Y_MAX, $plotBeginPos->z + $plotSize));
			$cuboid = Cuboid::fromSelection($selection);
			//$cuboid = $cuboid->async();
			$cuboid->set($plotBeginPos->level, Block::get(Block::AIR), function (float $time, int $changed) use ($plugin) : void {
				$plugin->getLogger()->debug('Set ' . number_format($changed) . ' blocks in ' . number_format($time, 10) . 's');
			});
			$styler->removeSelection(99998);
			// Ground Surface
			$selection = $styler->getSelection(99998);
			$plotBeginPos->y = $plotLevel->groundHeight;
			$selection->setPosition(1, $plotBeginPos);
			$selection->setPosition(2, new Vector3($plotBeginPos->x + $plotSize, $plotLevel->groundHeight, $plotBeginPos->z + $plotSize));
			$cuboid = Cuboid::fromSelection($selection);
			//$cuboid = $cuboid->async();
			$cuboid->set($plotBeginPos->level, $plotLevel->plotFloorBlock, function (float $time, int $changed) use ($plugin) : void {
				$plugin->getLogger()->debug('Set ' . number_format($changed) . ' blocks in ' . number_format($time, 10) . 's');
			});
			$styler->removeSelection(99998);
			// Ground
			$selection = $styler->getSelection(99998);
			$plotBeginPos->y = 1;
			$selection->setPosition(1, $plotBeginPos);
			$selection->setPosition(2, new Vector3($plotBeginPos->x + $plotSize, $plotLevel->groundHeight-1, $plotBeginPos->z + $plotSize));
			$cuboid = Cuboid::fromSelection($selection);
			//$cuboid = $cuboid->async();
			$cuboid->set($plotBeginPos->level, $plotLevel->plotFillBlock, function (float $time, int $changed) use ($plugin) : void {
				$plugin->getLogger()->debug('Set ' . number_format($changed) . ' blocks in ' . number_format($time, 10) . 's');
			});
			$styler->removeSelection(99998);
			// Bottom of world
			$selection = $styler->getSelection(99998);
			$plotBeginPos->y = 0;
			$selection->setPosition(1, $plotBeginPos);
			$selection->setPosition(2, new Vector3($plotBeginPos->x + $plotSize, 0, $plotBeginPos->z + $plotSize));
			$cuboid = Cuboid::fromSelection($selection);
			//$cuboid = $cuboid->async();
			$cuboid->set($plotBeginPos->level, $plotLevel->bottomBlock, function (float $time, int $changed) use ($plugin) : void {
				$plugin->getLogger()->debug('Set ' . number_format($changed) . ' blocks in ' . number_format($time, 10) . 's');
			});
			$styler->removeSelection(99998);
			return true;
		}
		$this->getScheduler()->scheduleTask(new ClearPlotTask($this, $plot, $maxBlocksPerTick));
		return true;
	}

	/**
	 * Delete the plot data
	 *
	 * @api
	 *
	 * @param Plot $plot
	 *
	 * @return bool
	 */
	public function disposePlot(Plot $plot) : bool {
		$ev = new MyPlotDisposeEvent($plot);
		$ev->call();
		if($ev->isCancelled()) {
			return false;
		}
		return $this->dataProvider->deletePlot($plot);
	}

	/**
	 * Clear and dispose a plot
	 *
	 * @api
	 *
	 * @param Plot $plot
	 * @param int $maxBlocksPerTick
	 *
	 * @return bool
	 */
	public function resetPlot(Plot $plot, int $maxBlocksPerTick = 256) : bool {
		$ev = new MyPlotResetEvent($plot);
		$ev->call();
		if($ev->isCancelled())
			return false;
		if($this->disposePlot($plot)) {
			return $this->clearPlot($plot, $maxBlocksPerTick);
		}
		return false;
	}

	/**
	 * Changes the biome of a plot
	 *
	 * @api
	 *
	 * @param Plot $plot
	 * @param Biome $biome
	 *
	 * @return bool
	 */
	public function setPlotBiome(Plot $plot, Biome $biome) : bool {
		$newPlot = clone $plot;
		$newPlot->biome = strtoupper($biome->getName());
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$ev->call();
		if($ev->isCancelled()) {
			return false;
		}
		$plot = $ev->getPlot();
		$biome = Biome::getBiome(constant(Biome::class . "::" . $plot->biome) ?? Biome::PLAINS);
		$plotLevel = $this->getLevelSettings($plot->levelName);
		if($plotLevel === null) {
			return false;
		}
		$level = $this->getServer()->getLevelByName($plot->levelName);
		$pos = $this->getPlotPosition($plot);
		$plotSize = $plotLevel->plotSize;
		$xMax = $pos->x + $plotSize;
		$zMax = $pos->z + $plotSize;
		$chunkIndexes = [];
		for($x = $pos->x; $x < $xMax; $x++) {
			for($z = $pos->z; $z < $zMax; $z++) {
				$index = Level::chunkHash($x >> 4, $z >> 4);
				if(!in_array($index, $chunkIndexes)) {
					$chunkIndexes[] = $index;
				}
				Level::getXZ($index, $cx, $cz);
				$chunk = $level->getChunk($cx, $cz, true);
				$chunk->setBiomeId($x, $z, $biome->getId());
			}
		}
		foreach($chunkIndexes as $index) {
			Level::getXZ($index, $cx, $cz);
			$chunk = $level->getChunk($cx, $cz, true);
			foreach($level->getViewersForPosition(new Vector3($cx, 0, $cz)) as $player) {
				$player->onChunkChanged($chunk);
			}
		}
		$this->savePlot($plot);
		return true;
	}

	/**
	 * @param Plot $plot
	 * @param bool $pvp
	 *
	 * @return bool
	 */
	public function setPlotPvp(Plot $plot, bool $pvp) : bool {
		$newPlot = clone $plot;
		$newPlot->pvp = $pvp;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$ev->call();
		if($ev->isCancelled()) {
			return false;
		}
		return $this->savePlot($ev->getPlot());
	}

	/**
	 * @param Plot $plot
	 * @param string $player
	 *
	 * @return bool
	 */
	public function addPlotHelper(Plot $plot, string $player) : bool {
		$newPlot = clone $plot;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$ev->setCancelled(!$newPlot->addHelper($player));
		$ev->call();
		if($ev->isCancelled()) {
			return false;
		}
		return $this->savePlot($ev->getPlot());
	}

	/**
	 * @param Plot $plot
	 * @param string $player
	 *
	 * @return bool
	 */
	public function removePlotHelper(Plot $plot, string $player) : bool {
		$newPlot = clone $plot;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$ev->setCancelled(!$newPlot->removeHelper($player));
		$ev->call();
		if($ev->isCancelled()) {
			return false;
		}
		return $this->savePlot($ev->getPlot());
	}

	/**
	 * @param Plot $plot
	 * @param string $player
	 *
	 * @return bool
	 */
	public function addPlotDenied(Plot $plot, string $player) : bool {
		$newPlot = clone $plot;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$ev->setCancelled(!$newPlot->denyPlayer($player));
		$ev->call();
		if($ev->isCancelled()) {
			return false;
		}
		return $this->savePlot($ev->getPlot());
	}

	/**
	 * @param Plot $plot
	 * @param string $player
	 *
	 * @return bool
	 */
	public function removePlotDenied(Plot $plot, string $player) : bool {
		$newPlot = clone $plot;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$ev->setCancelled(!$newPlot->unDenyPlayer($player));
		$ev->call();
		if($ev->isCancelled()) {
			return false;
		}
		return $this->savePlot($ev->getPlot());
	}

	/**
	 * Returns the PlotLevelSettings of all the loaded levels
	 *
	 * @api
	 *
	 * @return PlotLevelSettings[]
	 */
	public function getPlotLevels() : array {
		return $this->levels;
	}

	/**
	 * Returns the Chunks contained in a plot
	 *
	 * @api
	 *
	 * @param Plot $plot
	 *
	 * @return Chunk[]
	 */
	public function getPlotChunks(Plot $plot) : array {
		$plotLevel = $this->getLevelSettings($plot->levelName);
		$level = $this->getServer()->getLevelByName($plot->levelName);
		$pos = $this->getPlotPosition($plot);
		$plotSize = $plotLevel->plotSize;
		$xMax = ($pos->x + $plotSize) >> 4;
		$zMax = ($pos->z + $plotSize) >> 4;
		$chunks = [];
		for($x = $pos->x >> 4; $x < $xMax; $x++) {
			for($z = $pos->z >> 4; $z < $zMax; $z++) {
				$chunks[] = $level->getChunk($x, $z, true);
			}
		}
		return $chunks;
	}

	/**
	 * Get the maximum number of plots a player can claim
	 *
	 * @api
	 *
	 * @param Player $player
	 *
	 * @return int
	 */
	public function getMaxPlotsOfPlayer(Player $player) : int {
		if($player->hasPermission("myplot.claimplots.unlimited"))
			return PHP_INT_MAX;
		/** @var Permission[] $perms */
		$perms = array_merge(PermissionManager::getInstance()->getDefaultPermissions($player->isOp()), $player->getEffectivePermissions());
		$perms = array_filter($perms, function($name) {
			return (substr($name, 0, 18) === "myplot.claimplots.");
		}, ARRAY_FILTER_USE_KEY);
		if(count($perms) === 0)
			return 0;
		krsort($perms, SORT_FLAG_CASE | SORT_NATURAL);
		foreach($perms as $name => $perm) {
			$maxPlots = substr($name, 18);
			if(is_numeric($maxPlots)) {
				return (int) $maxPlots;
			}
		}
		return 0;
	}

	/**
	 * Finds the exact center of the plot at ground level
	 *
	 * @api
	 *
	 * @param Plot $plot
	 *
	 * @return Position|null
	 */
	public function getPlotMid(Plot $plot) : ?Position {
		$plotLevel = $this->getLevelSettings($plot->levelName);
		if($plotLevel === null) {
			return null;
		}
		$plotSize = $plotLevel->plotSize;
		$pos = $this->getPlotPosition($plot);
		$pos = new Position($pos->x + ($plotSize / 2), $pos->y + 1, $pos->z + ($plotSize / 2));
		return $pos;
	}

	/**
	 * Teleports the player to the exact center of the plot at nearest open space to the ground level
	 *
	 * @internal
	 *
	 * @param Plot $plot
	 * @param Player $player
	 *
	 * @return bool
	 */
	private function teleportMiddle(Player $player, Plot $plot) : bool {
		$mid = $this->getPlotMid($plot);
		if($mid === null) {
			return false;
		}
		return $player->teleport($mid);
	}

	/* -------------------------- Non-API part -------------------------- */
	public function onLoad() : void {
		$this->getLogger()->debug(TF::BOLD."Loading...");
		self::$instance = $this;
		$this->getLogger()->debug(TF::BOLD . "Loading Configs");
		$this->reloadConfig();
		@mkdir($this->getDataFolder() . "worlds");
		$this->getLogger()->debug(TF::BOLD . "Loading MyPlot Generator");
		GeneratorManager::addGenerator(MyPlotGenerator::class, "myplot", true);
		$this->getLogger()->debug(TF::BOLD . "Loading Languages");
		// Loading Languages
		/** @var string $lang */
		$lang = $this->getConfig()->get("Language", BaseLang::FALLBACK_LANGUAGE);
		if($this->getConfig()->get("Custom Messages", false)) {
			if(!file_exists($this->getDataFolder()."lang.ini")) {
				$resource = $this->getResource($lang.".ini") ?? file_get_contents($this->getFile()."resources/".BaseLang::FALLBACK_LANGUAGE.".ini");
				file_put_contents($this->getDataFolder()."lang.ini", $resource);
				if(!is_string($resource)) {
					fclose($resource);
				}
				$this->saveResource(BaseLang::FALLBACK_LANGUAGE.".ini", true);
				$this->getLogger()->debug("Custom Language ini created");
			}
			$this->baseLang = new BaseLang("lang", $this->getDataFolder());
		}else{
			if(file_exists($this->getDataFolder()."lang.ini")) {
				unlink($this->getDataFolder()."lang.ini");
				unlink($this->getDataFolder().BaseLang::FALLBACK_LANGUAGE.".ini");
				$this->getLogger()->debug("Custom Language ini deleted");
			}
			$this->baseLang = new BaseLang($lang, $this->getFile() . "resources/");
		}
		$this->getLogger()->debug(TF::BOLD . "Loading Data Provider settings");
		// Initialize DataProvider
		/** @var int $cacheSize */
		$cacheSize = $this->getConfig()->get("PlotCacheSize", 256);
		switch(strtolower($this->getConfig()->get("DataProvider", "sqlite3"))) {
			case "mysqli":
			case "mysql":
				if(extension_loaded("mysqli")) {
					$settings = $this->getConfig()->get("MySQLSettings");
					$this->dataProvider = new MySQLProvider($this, $cacheSize, $settings);
				}else {
					$this->getLogger()->info("MySQLi is not installed in your php build! JSON will be used instead.");
					$this->dataProvider = new JSONDataProvider($this, $cacheSize);
				}
			break;
			case "yaml":
				if(extension_loaded("yaml")) {
					$this->dataProvider = new YAMLDataProvider($this, $cacheSize);
				}else {
					$this->getLogger()->info("YAML is not installed in your php build! JSON will be used instead.");
					$this->dataProvider = new JSONDataProvider($this, $cacheSize);
				}
			break;
			case "sqlite3":
			case "sqlite":
				if(extension_loaded("sqlite3")) {
					$this->dataProvider = new SQLiteDataProvider($this, $cacheSize);
				}else {
					$this->getLogger()->info("SQLite3 is not installed in your php build! JSON will be used instead.");
					$this->dataProvider = new JSONDataProvider($this, $cacheSize);
				}
			break;
			case "json":
			default:
				$this->dataProvider = new JSONDataProvider($this, $cacheSize);
			break;
		}
		$this->getLogger()->debug(TF::BOLD . "Loading Plot Clearing settings");
		if($this->getConfig()->get("FastClearing", false) and $this->getServer()->getPluginManager()->getplugin("WorldStyler") === null) {
			$this->getConfig()->set("FastClearing", false);
			$this->getLogger()->info(TF::BOLD . "WorldStyler not found. Legacy clearing will be used.");
		}
		$this->getLogger()->debug(TF::BOLD . "Loading MyPlot Commands");
		// Register command
		$this->getServer()->getCommandMap()->register("myplot", new Commands($this));
	}

	public function onEnable() : void {
		SpoonDetector::printSpoon($this, "spoon.txt");
		if($this->isDisabled()) {
			return;
		}
		$this->getLogger()->debug(TF::BOLD . "Loading economy settings");
		// Initialize EconomyProvider
		if($this->getConfig()->get("UseEconomy", false) === true) {
			if(($plugin = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")) !== null) {
				if($plugin instanceof EconomyAPI) {
					$this->economyProvider = new EconomySProvider($plugin);
					$this->getLogger()->debug("Eco set to EconomySProvider");
				}else
				$this->getLogger()->debug("Eco not instance of EconomyAPI");
			}
			elseif(($plugin = $this->getServer()->getPluginManager()->getPlugin("EssentialsPE")) !== null) {
				if($plugin instanceof Loader) {
					$this->economyProvider = new EssentialsPEProvider($plugin);
					$this->getLogger()->debug("Eco set to EssentialsPE");
				}else
				$this->getLogger()->debug("Eco not instance of EssentialsPE");
			}
			elseif(($plugin = $this->getServer()->getPluginManager()->getPlugin("PocketMoney")) !== null) {
				if($plugin instanceof PocketMoney) {
					$this->economyProvider = new PocketMoneyProvider($plugin);
					$this->getLogger()->debug("Eco set to PocketMoney");
				}else
				$this->getLogger()->debug("Eco not instance of PocketMoney");
			}
			if(!isset($this->economyProvider)) {
				$this->getLogger()->info("No supported economy plugin found!");
				$this->getConfig()->set("UseEconomy", false);
				//$this->getConfig()->save();
			}
		}
		$this->getLogger()->debug(TF::BOLD . "Loading Events");
		$eventListener = new EventListener($this);
		$this->getServer()->getPluginManager()->registerEvents($eventListener, $this);
		$this->getLogger()->debug(TF::BOLD . "Registering Loaded Levels");
		foreach($this->getServer()->getLevels() as $level) {
			$eventListener->onLevelLoad(new LevelLoadEvent($level));
		}
		$this->getLogger()->debug(TF::BOLD.TF::GREEN."Enabled!");
	}

	/**
	 * @param string $levelName
	 * @param PlotLevelSettings $settings
	 *
	 * @return bool
	 */
	public function addLevelSettings(string $levelName, PlotLevelSettings $settings) : bool {
		$this->levels[$levelName] = $settings;
		return true;
	}

	/**
	 * @param string $levelName
	 *
	 * @return bool
	 */
	public function unloadLevelSettings(string $levelName) : bool {
		if(isset($this->levels[$levelName])) {
			unset($this->levels[$levelName]);
			$this->getLogger()->debug("Level " . $levelName . " settings unloaded!");
			return true;
		}
		return false;
	}

	public function onDisable() : void {
		if($this->dataProvider !== null)
			$this->dataProvider->close();
	}
}
