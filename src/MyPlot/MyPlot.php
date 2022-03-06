<?php
declare(strict_types=1);
namespace MyPlot;

use muqsit\worldstyler\WorldStyler;
use MyPlot\events\MyPlotClearEvent;
use MyPlot\events\MyPlotCloneEvent;
use MyPlot\events\MyPlotDisposeEvent;
use MyPlot\events\MyPlotFillEvent;
use MyPlot\events\MyPlotGenerationEvent;
use MyPlot\events\MyPlotResetEvent;
use MyPlot\events\MyPlotSettingEvent;
use MyPlot\events\MyPlotTeleportEvent;
use MyPlot\plot\SinglePlot;
use MyPlot\provider\EconomyProvider;
use MyPlot\provider\EconomySProvider;
use onebone\economyapi\EconomyAPI;
use pocketmine\block\Block;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\lang\Language;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionAttachmentInfo;
use pocketmine\permission\PermissionManager;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use pocketmine\world\biome\Biome;
use pocketmine\world\biome\BiomeRegistry;
use pocketmine\world\format\Chunk;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\Position;
use pocketmine\world\WorldCreationOptions;

class MyPlot extends PluginBase
{
	private static MyPlot $instance;
	private Language $language;
	private ?EconomyProvider $economyProvider;
	private InternalAPI $internalAPI;

	public static function getInstance() : self {
		return self::$instance;
	}

	/**
	 * Returns the Multi-lang management class
	 *
	 * @api
	 *
	 * @return Language
	 */
	public function getLanguage() : Language {
		return $this->language;
	}

	/**
	 * Returns the fallback language class
	 *
	 * @internal
	 *
	 * @return Language
	 */
	public function getFallBackLang() : Language {
		return new Language(Language::FALLBACK_LANGUAGE, $this->getFile() . "resources/");
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
			$this->getConfig()->set("UseEconomy", true);
			$this->getLogger()->info("A custom economy provider has been registered. Economy mode now enabled!");
		}
		$this->economyProvider = $provider;
	}

	/**
	 * Returns the PlotLevelSettings of all the loaded Worlds
	 *
	 * @api
	 *
	 * @return PlotLevelSettings[]
	 */
	public function getPlotLevels() : array {
		return $this->internalAPI->getAllLevelSettings();
	}

	/**
	 * Returns a PlotLevelSettings object which contains all the settings of a world
	 *
	 * @api
	 *
	 * @param string $levelName
	 *
	 * @return PlotLevelSettings|null
	 */
	public function getLevelSettings(string $levelName) : ?PlotLevelSettings {
		return $this->internalAPI->getLevelSettings($levelName);
	}

	/**
	 * Registers a new PlotLevelSettings object to the loaded worlds list
	 *
	 * @api
	 *
	 * @param string            $levelName
	 * @param PlotLevelSettings $settings
	 *
	 * @return void
	 */
	public function addLevelSettings(string $levelName, PlotLevelSettings $settings) : void {
		$this->internalAPI->addLevelSettings($levelName, $settings);
	}

	/**
	 * Unregisters a PlotLevelSettings object from the loaded worlds list
	 *
	 * @api
	 *
	 * @param string $levelName
	 *
	 * @return bool
	 */
	public function unloadLevelSettings(string $levelName) : bool {
		return $this->internalAPI->unloadLevelSettings($levelName);
	}


	/**
	 * Generate a new plot level with optional settings
	 *
	 * @api
	 *
	 * @param string $levelName
	 * @param string $generator
	 * @param mixed[] $settings
	 *
	 * @return bool
	 */
	public function generateLevel(string $levelName, string $generator = MyPlotGenerator::NAME, array $settings = []) : bool {
		$ev = new MyPlotGenerationEvent($levelName, $generator, $settings);
		$ev->call();
		if($ev->isCancelled() or $this->getServer()->getWorldManager()->isWorldGenerated($levelName)) {
			return false;
		}
		$generator = GeneratorManager::getInstance()->getGenerator($generator);
		if(count($settings) === 0) {
			$this->getConfig()->reload();
			$settings = $this->getConfig()->get("DefaultWorld", []);
		}
		$default = array_filter((array) $this->getConfig()->get("DefaultWorld", []), function($key) : bool {
			return !in_array($key, ["PlotSize", "GroundHeight", "RoadWidth", "RoadBlock", "WallBlock", "PlotFloorBlock", "PlotFillBlock", "BottomBlock"], true);
		}, ARRAY_FILTER_USE_KEY);
		new Config($this->getDataFolder()."worlds".DIRECTORY_SEPARATOR.$levelName.".yml", Config::YAML, $default);
		$return = $this->getServer()->getWorldManager()->generateWorld($levelName, WorldCreationOptions::create()->setGeneratorClass($generator->getGeneratorClass())->setGeneratorOptions(json_encode($settings)), true);
		$level = $this->getServer()->getWorldManager()->getWorldByName($levelName);
		$level?->setSpawnLocation(new Vector3(0, $this->getConfig()->getNested("DefaultWorld.GroundHeight", 64) + 1, 0));
		return $return;
	}

	/**
	 * Saves provided plot if changed
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function savePlot(SinglePlot $plot) : Promise{
		$resolver = new PromiseResolver();
		$this->internalAPI->savePlot(
			$plot,
			fn(bool $success) => $resolver->resolve($success),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * Get all the plots a player owns (in a certain level if $levelName is provided)
	 *
	 * @api
	 *
	 * @param string      $username
	 * @param string|null $levelName
	 *
	 * @return Promise
	 * @phpstan-return Promise<array<SinglePlot>>
	 */
	public function getPlotsOfPlayer(string $username, ?string $levelName = null) : Promise{
		$resolver = new PromiseResolver();
		$this->internalAPI->getPlotsOfPlayer(
			$username,
			$levelName,
			fn(array $plots) => $resolver->resolve($plots),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * Get the next free plot in a level
	 *
	 * @api
	 *
	 * @param string $levelName
	 * @param int    $limitXZ
	 *
	 * @return Promise
	 * @phpstan-return Promise<SinglePlot|null>
	 */
	public function getNextFreePlot(string $levelName, int $limitXZ = 0) : Promise{
		$resolver = new PromiseResolver();
		$this->internalAPI->getNextFreePlot(
			$levelName,
			$limitXZ,
			fn(?SinglePlot $plot) => $resolver->resolve($plot),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * Finds the plot at a certain position or null if there is no plot at that position
	 *
	 * @api
	 *
	 * @param Position $position
	 *
	 * @return Promise
	 * @phpstan-return Promise<SinglePlot|null>
	 */
	public function getPlotByPosition(Position $position) : Promise{
		$resolver = new PromiseResolver();
		$this->internalAPI->getPlotByPosition(
			$position,
			fn(?SinglePlot $plot) => $resolver->resolve($plot),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * Get the beginning position of a plot
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 * @param bool       $mergeOrigin
	 *
	 * @return Promise
	 * @phpstan-return Promise<Position>
	 */
	public function getPlotPosition(SinglePlot $plot, bool $mergeOrigin = true) : Promise{
		$resolver = new PromiseResolver();
		$this->internalAPI->getPlotPosition(
			$plot,
			$mergeOrigin,
			fn(Position $position) => $resolver->resolve($position),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * Detects if the given position is bordering a plot
	 *
	 * @api
	 *
	 * @param Position $position
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function isPositionBorderingPlot(Position $position) : Promise{
		$resolver = new PromiseResolver();
		$this->internalAPI->isPositionBorderingPlot(
			$position,
			fn(bool $bordering) => $resolver->resolve($bordering),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * Retrieves the plot adjacent to teh given position
	 *
	 * @api
	 *
	 * @param Position $position
	 *
	 * @return Promise
	 * @phpstan-return Promise<SinglePlot|null>
	 */
	public function getPlotBorderingPosition(Position $position) : Promise{
		$resolver = new PromiseResolver();
		$this->internalAPI->getPlotBorderingPosition(
			$position,
			fn(SinglePlot $plot) => $resolver->resolve($plot),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * Returns the AABB of the plot area
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 *
	 * @return Promise
	 * @phpstan-return Promise<AxisAlignedBB>
	 */
	public function getPlotBB(SinglePlot $plot) : Promise{
		$resolver = new PromiseResolver();
		$this->internalAPI->getPlotBB(
			$plot,
			fn(AxisAlignedBB $bb) => $resolver->resolve($bb),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * TODO: description
	 *
	 * @api
	 *
	 * @param SinglePlot $plot The plot that is to be expanded
	 * @param int        $direction The Vector3 direction value to expand towards
	 * @param int        $maxBlocksPerTick
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function mergePlots(SinglePlot $plot, int $direction, int $maxBlocksPerTick = 256) : Promise{
		$resolver = new PromiseResolver();
		$this->internalAPI->mergePlots(
			$plot,
			$direction,
			$maxBlocksPerTick,
			fn(bool $success) => $resolver->resolve($success),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * Teleport a player to a plot
	 *
	 * @api
	 *
	 * @param Player     $player
	 * @param SinglePlot $plot
	 * @param bool       $center
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function teleportPlayerToPlot(Player $player, SinglePlot $plot, bool $center = false) : Promise {
		$resolver = new PromiseResolver();
		$ev = new MyPlotTeleportEvent($plot, $player, $center);
		$ev->call();
		if($ev->isCancelled()){
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		$this->internalAPI->teleportPlayerToPlot(
			$player,
			$plot,
			$center,
			fn(bool $success) => $resolver->resolve($success),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * Claims a plot in a players name
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 * @param string     $claimer
	 * @param string     $plotName
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function claimPlot(SinglePlot $plot, string $claimer, string $plotName = "") : Promise{
		$newPlot = clone $plot;
		$newPlot->owner = $claimer;
		$newPlot->helpers = [];
		$newPlot->denied = [];
		if($plotName !== "")
			$newPlot->name = $plotName;
		$newPlot->price = 0.0;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$ev->call();
		if($ev->isCancelled()){
			$resolver = new PromiseResolver();
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		return $this->savePlot($ev->getPlot());
	}

	/**
	 * Renames a plot
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 * @param string     $newName
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function renamePlot(SinglePlot $plot, string $newName = "") : Promise{
		$newPlot = clone $plot;
		$newPlot->name = $newName;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$ev->call();
		if($ev->isCancelled()){
			$resolver = new PromiseResolver();
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		return $this->savePlot($ev->getPlot());
	}

	/**
	 * Clones a plot to another location
	 *
	 * @api
	 *
	 * @param SinglePlot $plotFrom
	 * @param SinglePlot $plotTo
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function clonePlot(SinglePlot $plotFrom, SinglePlot $plotTo) : Promise {
		$resolver = new PromiseResolver();
		$styler = $this->getServer()->getPluginManager()->getPlugin("WorldStyler");
		if(!$styler instanceof WorldStyler) {
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		$ev = new MyPlotCloneEvent($plotFrom, $plotTo);
		$ev->call();
		if($ev->isCancelled()) {
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		$plotFrom = $ev->getPlot();
		$plotTo = $ev->getClonePlot();
		if($this->internalAPI->getLevelSettings($plotFrom->levelName) === null or $this->internalAPI->getLevelSettings($plotTo->levelName) === null) {
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		$this->internalAPI->clonePlot(
			$plotFrom,
			$plotTo,
			$styler,
			fn(bool $success) => $resolver->resolve($success),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * Reset all the blocks inside a plot
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 * @param int        $maxBlocksPerTick
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function clearPlot(SinglePlot $plot, int $maxBlocksPerTick = 256) : Promise {
		$resolver = new PromiseResolver();
		$ev = new MyPlotClearEvent($plot, $maxBlocksPerTick);
		$ev->call();
		if($ev->isCancelled()) {
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		$plot = $ev->getPlot();
		if($this->internalAPI->getLevelSettings($plot->levelName) === null) {
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		$maxBlocksPerTick = $ev->getMaxBlocksPerTick();
		$this->internalAPI->clearPlot(
			$plot,
			$maxBlocksPerTick,
			fn(bool $success) => $resolver->resolve($success),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * Fills the whole plot with a block
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 * @param Block      $plotFillBlock
	 * @param int        $maxBlocksPerTick
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function fillPlot(SinglePlot $plot, Block $plotFillBlock, int $maxBlocksPerTick = 256) : Promise {
		$resolver = new PromiseResolver();
		$ev = new MyPlotFillEvent($plot, $maxBlocksPerTick);
		$ev->call();
		if($ev->isCancelled()) {
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		$plot = $ev->getPlot();
		if($this->internalAPI->getLevelSettings($plot->levelName) === null) {
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		$maxBlocksPerTick = $ev->getMaxBlocksPerTick();
		$this->internalAPI->fillPlot(
			$plot,
			$plotFillBlock,
			$maxBlocksPerTick,
			fn(bool $success) => $resolver->resolve($success),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * Delete the plot data
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function disposePlot(SinglePlot $plot) : Promise {
		$resolver = new PromiseResolver();
		$ev = new MyPlotDisposeEvent($plot);
		$ev->call();
		if($ev->isCancelled()) {
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		$plot = $ev->getPlot();
		$this->internalAPI->disposePlot(
			$plot,
			fn(bool $success) => $resolver->resolve($success),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * Clear and dispose a plot
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 * @param int        $maxBlocksPerTick
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function resetPlot(SinglePlot $plot, int $maxBlocksPerTick = 256) : Promise {
		$resolver = new PromiseResolver();
		$ev = new MyPlotResetEvent($plot);
		$ev->call();
		if($ev->isCancelled()) {
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		$plot = $ev->getPlot();
		$this->internalAPI->resetPlot(
			$plot,
			$maxBlocksPerTick,
			fn(bool $success) => $resolver->resolve($success),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * Changes the biome of a plot
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 * @param Biome      $biome
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function setPlotBiome(SinglePlot $plot, Biome $biome) : Promise {
		$resolver = new PromiseResolver();
		$newPlot = clone $plot;
		$newPlot->biome = str_replace(" ", "_", strtoupper($biome->getName()));
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$ev->call();
		if($ev->isCancelled()) {
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		$plot = $ev->getPlot();
		if(defined(BiomeIds::class."::".$plot->biome) and is_int(constant(BiomeIds::class."::".$plot->biome))) {
			$biome = constant(BiomeIds::class."::".$plot->biome);
		}else{
			$biome = BiomeIds::PLAINS;
		}
		$biome = BiomeRegistry::getInstance()->getBiome($biome);
		if($this->internalAPI->getLevelSettings($plot->levelName) === null){
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		$this->internalAPI->setPlotBiome(
			$plot,
			$biome,
			fn(bool $success) => $resolver->resolve($success),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * TODO: description
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 * @param bool       $pvp
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function setPlotPvp(SinglePlot $plot, bool $pvp) : Promise {
		$newPlot = clone $plot;
		$newPlot->pvp = $pvp;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$ev->call();
		if($ev->isCancelled()) {
			$resolver = new PromiseResolver();
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		return $this->savePlot($ev->getPlot());
	}

	/**
	 * TODO: description
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 * @param string     $player
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function addPlotHelper(SinglePlot $plot, string $player) : Promise {
		$newPlot = clone $plot;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$newPlot->addHelper($player) ? $ev->uncancel() : $ev->cancel();
		$ev->call();
		if($ev->isCancelled()) {
			$resolver = new PromiseResolver();
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		return $this->savePlot($ev->getPlot());
	}

	/**
	 * TODO: description
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 * @param string     $player
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function removePlotHelper(SinglePlot $plot, string $player) : Promise {
		$newPlot = clone $plot;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$newPlot->removeHelper($player) ? $ev->uncancel() : $ev->cancel();
		$ev->call();
		if($ev->isCancelled()) {
			$resolver = new PromiseResolver();
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		return $this->savePlot($ev->getPlot());
	}

	/**
	 * TODO: description
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 * @param string     $player
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function addPlotDenied(SinglePlot $plot, string $player) : Promise {
		$newPlot = clone $plot;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$newPlot->denyPlayer($player) ? $ev->uncancel() : $ev->cancel();
		$ev->call();
		if($ev->isCancelled()) {
			$resolver = new PromiseResolver();
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		return $this->savePlot($ev->getPlot());
	}

	/**
	 * TODO: description
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 * @param string     $player
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function removePlotDenied(SinglePlot $plot, string $player) : Promise {
		$newPlot = clone $plot;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$newPlot->unDenyPlayer($player) ? $ev->uncancel() : $ev->cancel();
		$ev->call();
		if($ev->isCancelled()) {
			$resolver = new PromiseResolver();
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		return $this->savePlot($ev->getPlot());
	}

	/**
	 * Assigns a price to a plot
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 * @param float      $price
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function sellPlot(SinglePlot $plot, float $price) : Promise {
		$resolver = new PromiseResolver();
		if($this->getEconomyProvider() === null or $price < 0) {
			$resolver->resolve(false);
			return $resolver->getPromise();
		}

		$newPlot = clone $plot;
		$newPlot->price = $price;
		$ev = new MyPlotSettingEvent($plot, $newPlot);
		$ev->call();
		if($ev->isCancelled()) {
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		$plot = $ev->getPlot();
		return $this->savePlot($plot);
	}

	/**
	 * Resets the price, adds the money to the player's account and claims a plot in a players name
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 * @param Player     $player
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function buyPlot(SinglePlot $plot, Player $player) : Promise {
		$resolver = new PromiseResolver();
		if($this->getEconomyProvider() === null) {
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		if(!$this->getEconomyProvider()->reduceMoney($player, $plot->price)) {
			$resolver->resolve(false);
			return $resolver->getPromise();
		}
		if(!$this->getEconomyProvider()->addMoney($this->getServer()->getOfflinePlayer($plot->owner), $plot->price)) {
			$this->getEconomyProvider()->addMoney($player, $plot->price);
			$resolver->resolve(false);
			return $resolver->getPromise();
		}

		return $this->claimPlot($plot, $player->getName());
	}

	/**
	 * Returns the Chunks contained in a plot
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 *
	 * @return Promise<array<int, Chunk>>
	 */
	public function getPlotChunks(SinglePlot $plot) : Promise {
		$resolver = new PromiseResolver();
		$this->internalAPI->getPlotChunks(
			$plot,
			fn(array $chunks) => $resolver->resolve($chunks),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
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
		$perms = array_map(fn(PermissionAttachmentInfo $attachment) => $attachment->getValue(), $player->getEffectivePermissions()); // outputs permission string => value
		$perms = array_merge(PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_USER)->getChildren(), $perms);
		$perms = array_filter($perms, function(string $name) : bool {
			return (str_starts_with($name, "myplot.claimplots."));
		}, ARRAY_FILTER_USE_KEY);
		if(count($perms) === 0)
			return 0;
		krsort($perms, SORT_FLAG_CASE | SORT_NATURAL);
		/**
		 * @var string $name
		 * @var Permission $perm
		 */
		foreach($perms as $name => $perm) {
			$maxPlots = substr($name, 18);
			if(is_numeric($maxPlots)) {
				return (int) $maxPlots;
			}
		}
		return 0;
	}

	/* -------------------------- Non-API part -------------------------- */

	public function onLoad() : void{
		self::$instance = $this;

		$this->getLogger()->debug(TF::BOLD . "Loading Configs");
		$this->reloadConfig();
		@mkdir($this->getDataFolder() . "worlds");

		$this->getLogger()->debug(TF::BOLD . "Loading MyPlot Generator");
		GeneratorManager::getInstance()->addGenerator(MyPlotGenerator::class, "myplot", fn() => null, true);

		$this->getLogger()->debug(TF::BOLD . "Loading Languages");
		/** @var string $lang */
		$lang = $this->getConfig()->get("Language", Language::FALLBACK_LANGUAGE);
		if($this->getConfig()->get("Custom Messages", false) === true) {
			if(!file_exists($this->getDataFolder()."lang.ini")) {
				/** @var string|resource $resource */
				$resource = $this->getResource($lang.".ini") ?? file_get_contents($this->getFile()."resources/".Language::FALLBACK_LANGUAGE.".ini");
				file_put_contents($this->getDataFolder()."lang.ini", $resource);
				if(is_resource($resource)) {
					fclose($resource);
				}
				$this->saveResource(Language::FALLBACK_LANGUAGE.".ini", true);
				$this->getLogger()->debug("Custom Language ini created");
			}
			$this->language = new Language("lang", $this->getDataFolder());
		}else{
			if(file_exists($this->getDataFolder()."lang.ini")) {
				unlink($this->getDataFolder()."lang.ini");
				unlink($this->getDataFolder().Language::FALLBACK_LANGUAGE.".ini");
				$this->getLogger()->debug("Custom Language ini deleted");
			}
			$this->language = new Language($lang, $this->getFile() . "resources/");
		}
		$this->getLogger()->debug(TF::BOLD . "Loading Plot Clearing settings");
		if($this->getConfig()->get("FastClearing", false) === true and $this->getServer()->getPluginManager()->getPlugin("WorldStyler") === null) {
			$this->getConfig()->set("FastClearing", false);
			$this->getLogger()->info(TF::BOLD . "WorldStyler not found. Legacy clearing will be used.");
		}

		$this->getLogger()->debug(TF::BOLD . "Loading economy settings");
		if($this->getConfig()->get("UseEconomy", false) === true) {
			if(($plugin = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")) !== null) {
				if($plugin instanceof EconomyAPI) {
					$this->economyProvider = new EconomySProvider($plugin);
					$this->getLogger()->debug("Eco set to EconomySProvider");
				}else
					$this->getLogger()->debug("Eco not instance of EconomyAPI");
			}
			if(!isset($this->economyProvider)) {
				$this->getLogger()->info("No supported economy plugin found!");
				$this->getConfig()->set("UseEconomy", false);
				//$this->getConfig()->save();
			}
		}

		$this->getLogger()->debug(TF::BOLD . "Loading MyPlot Commands");
		$this->getServer()->getCommandMap()->register("myplot", new Commands($this));

		$this->internalAPI = new InternalAPI($this);
	}

	public function onEnable() : void {
		$this->getLogger()->debug(TF::BOLD . "Loading Events");
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this, $this->internalAPI), $this);
	}

	public function onDisable() : void {
		$this->internalAPI->onDisable();
	}
}