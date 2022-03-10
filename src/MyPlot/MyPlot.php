<?php
declare(strict_types=1);
namespace MyPlot;

use MyPlot\events\MyPlotGenerationEvent;
use MyPlot\plot\BasePlot;
use MyPlot\plot\SinglePlot;
use MyPlot\provider\EconomyWrapper;
use MyPlot\provider\InternalCapitalProvider;
use MyPlot\provider\InternalEconomyProvider;
use MyPlot\provider\InternalEconomySProvider;
use onebone\economyapi\EconomyAPI;
use pocketmine\block\Block;
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
use pocketmine\world\format\Chunk;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\Position;
use pocketmine\world\WorldCreationOptions;
use SOFe\Capital\Capital;

final class MyPlot extends PluginBase{
	private static MyPlot $instance;
	private Language $language;
	private InternalAPI $internalAPI;
	private ?EconomyWrapper $economyProvider;

	public static function getInstance() : self{
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
	 * @return InternalEconomyProvider|null
	 * @api
	 *
	 */
	public function getEconomyProvider() : ?EconomyWrapper{
		return $this->economyProvider;
	}

	/**
	 * Allows setting the economy provider to a custom provider or to null to disable economy mode
	 *
	 * @param bool $enable
	 *
	 * @api
	 *
	 */
	public function toggleEconomy(bool $enable) : void{
		if(!$enable){
			$this->getLogger()->info("Economy mode has been disabled via API");
			$this->internalAPI->setEconomyProvider(null);
			$this->economyProvider = null;
			$this->getConfig()->set("UseEconomy", false);
		}else{
			$this->getLogger()->info("Economy mode has been enabled via API");
			$this->internalAPI->setEconomyProvider($this->checkEconomy());
		}
	}

	/**
	 * Returns the PlotLevelSettings of all the loaded Worlds
	 *
	 * @api
	 *
	 * @return PlotLevelSettings[]
	 */
	public function getAllLevelSettings() : array{
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
	 * @param string $levelName
	 * @param int    $X
	 * @param int    $Z
	 *
	 * @return Promise<SinglePlot|null>
	 */
	public function getPlot(string $levelName, int $X, int $Z) : Promise{
		$resolver = new PromiseResolver();
		$this->internalAPI->getPlot(
			new BasePlot($levelName, $X, $Z),
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
	 * @param BasePlot $plot
	 *
	 * @return Position
	 * @api
	 *
	 */
	public function getPlotPosition(BasePlot $plot) : Position{
		return $this->internalAPI->getPlotPosition($plot);
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
	 * @param SinglePlot $plot
	 *
	 * @return AxisAlignedBB
	 * @api
	 *
	 */
	public function getPlotBB(SinglePlot $plot) : AxisAlignedBB{
		return $this->internalAPI->getPlotBB($plot);
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
	 * @return bool
	 */
	public function teleportPlayerToPlot(Player $player, BasePlot $plot, bool $center = false) : bool{
		return $this->internalAPI->teleportPlayerToPlot($player, $plot, $center);
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
		$resolver = new PromiseResolver();
		$this->internalAPI->claimPlot(
			$plot,
			$claimer,
			$plotName,
			fn(bool $success) => $resolver->resolve($success),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
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
		$resolver = new PromiseResolver();
		$this->internalAPI->renamePlot(
			$plot,
			$newName,
			fn(bool $success) => $resolver->resolve($success),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * Clones a plot to another location
	 *
	 * @api
	 *
	 * @param SinglePlot $plotFrom
	 * @param SinglePlot $plotTo
	 *
	 * @return bool
	 */
	public function clonePlot(SinglePlot $plotFrom, SinglePlot $plotTo) : bool{
		return $this->internalAPI->clonePlot($plotFrom, $plotTo);
	}

	/**
	 * Reset all the blocks inside a plot
	 *
	 * @param BasePlot $plot
	 * @param int      $maxBlocksPerTick
	 *
	 * @return bool
	 * @api
	 *
	 */
	public function clearPlot(BasePlot $plot, int $maxBlocksPerTick = 256) : bool{
		return $this->internalAPI->clearPlot($plot, $maxBlocksPerTick);
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
	public function fillPlot(BasePlot $plot, Block $plotFillBlock, int $maxBlocksPerTick = 256) : Promise{
		$resolver = new PromiseResolver();
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
	public function setPlotPvp(SinglePlot $plot, bool $pvp) : Promise{
		$resolver = new PromiseResolver();
		$this->internalAPI->setPlotPvp(
			$plot,
			$pvp,
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
	 * @param string     $player
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function addPlotHelper(SinglePlot $plot, string $player) : Promise{
		$resolver = new PromiseResolver();
		$this->internalAPI->addPlotHelper(
			$plot,
			$player,
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
	 * @param string     $player
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function removePlotHelper(SinglePlot $plot, string $player) : Promise{
		$resolver = new PromiseResolver();
		$this->internalAPI->removePlotHelper(
			$plot,
			$player,
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
	 * @param string     $player
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function addPlotDenied(SinglePlot $plot, string $player) : Promise{
		$resolver = new PromiseResolver();
		$this->internalAPI->addPlotDenied(
			$plot,
			$player,
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
	 * @param string     $player
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function removePlotDenied(SinglePlot $plot, string $player) : Promise{
		$resolver = new PromiseResolver();
		$this->internalAPI->removePlotDenied(
			$plot,
			$player,
			fn(bool $success) => $resolver->resolve($success),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * Assigns a price to a plot
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 * @param int        $price
	 *
	 * @return Promise
	 * @phpstan-return Promise<bool>
	 */
	public function sellPlot(SinglePlot $plot, int $price) : Promise{
		$resolver = new PromiseResolver();
		$this->internalAPI->sellPlot(
			$plot,
			$price,
			fn(bool $success) => $resolver->resolve($success),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
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
	public function buyPlot(SinglePlot $plot, Player $player) : Promise{
		$resolver = new PromiseResolver();
		$this->internalAPI->buyPlot(
			$plot,
			$player,
			fn(bool $success) => $resolver->resolve($success),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * Returns the Chunks contained in a plot
	 *
	 * @api
	 *
	 * @param SinglePlot $plot
	 *
	 * @return array
	 * @phpstan-return array<array<int|Chunk|null>>
	 */
	public function getPlotChunks(BasePlot $plot) : array{
		$this->internalAPI->getPlotChunks($plot);
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
		foreach($perms as $name => $perm){
			$maxPlots = substr($name, 18);
			if(is_numeric($maxPlots)){
				return (int) $maxPlots;
			}
		}
		return 0;
	}

	/* -------------------------- Non-API part -------------------------- */

	private function checkEconomy() : InternalEconomySProvider{
		$this->getLogger()->debug(TF::BOLD . "Loading economy settings");
		$this->economyProvider = $economyProvider = null;
		if(($plugin = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")) !== null){
			if($plugin instanceof EconomyAPI){
				$economyProvider = new InternalEconomySProvider($plugin);
				$this->economyProvider = new EconomyWrapper($economyProvider);
				$this->getLogger()->info("Economy set to EconomyAPI");
			}else
				$this->getLogger()->debug("Invalid instance of EconomyAPI");
		}
		if(($plugin = $this->getServer()->getPluginManager()->getPlugin("Capital")) !== null){
			if($plugin instanceof Capital){
				$economyProvider = new InternalCapitalProvider();
				$this->economyProvider = new EconomyWrapper($economyProvider);
				$this->getLogger()->info("Economy set to Capital");
			}else
				$this->getLogger()->debug("Invalid instance of Capital");
		}
		if(!isset($economyProvider)){
			$this->getLogger()->warning("No supported economy plugin found!");
			$this->getConfig()->set("UseEconomy", false);
			//$this->getConfig()->save();
		}
		return $economyProvider;
	}

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

		$this->getLogger()->debug(TF::BOLD . "Loading Plot Clearing settings"); // TODO: finish libEfficientWE
		if($this->getConfig()->get("FastClearing", false) === true and $this->getServer()->getPluginManager()->getPlugin("WorldStyler") === null){
			$this->getConfig()->set("FastClearing", false);
			$this->getLogger()->info(TF::BOLD . "WorldStyler not found. Legacy clearing will be used.");
		}

		$this->internalAPI = new InternalAPI(
			$this,
			$this->getConfig()->get("UseEconomy", false) === true ? $this->checkEconomy() : null
		);

		$this->getLogger()->debug(TF::BOLD . "Loading MyPlot Commands");
		$this->getServer()->getCommandMap()->register("myplot", new Commands($this, $this->internalAPI));
	}

	public function onEnable() : void {
		$this->getLogger()->debug(TF::BOLD . "Loading Events");
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this, $this->internalAPI), $this);
	}

	public function onDisable() : void {
		$this->internalAPI->onDisable();
	}
}