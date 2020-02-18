<?php
declare(strict_types=1);
namespace MyPlot;

use MyPlot\events\MyPlotBlockEvent;
use MyPlot\events\MyPlotBorderChangeEvent;
use MyPlot\events\MyPlotPlayerEnterPlotEvent;
use MyPlot\events\MyPlotPlayerLeavePlotEvent;
use MyPlot\events\MyPlotPvpEvent;
use pocketmine\block\Sapling;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\level\LevelUnloadEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class EventListener implements Listener
{
	/** @var MyPlot $plugin */
	private $plugin;

	/**
	 * EventListener constructor.
	 *
	 * @param MyPlot $plugin
	 */
	public function __construct(MyPlot $plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param LevelLoadEvent $event
	 */
	public function onLevelLoad(LevelLoadEvent $event) : void {
		if(file_exists($this->plugin->getDataFolder()."worlds".DIRECTORY_SEPARATOR.$event->getLevel()->getFolderName().".yml")) {
			$this->plugin->getLogger()->debug("MyPlot level " . $event->getLevel()->getFolderName() . " loaded!");
			$settings = $event->getLevel()->getProvider()->getGeneratorOptions();
			if(!isset($settings["preset"]) or empty($settings["preset"])) {
				return;
			}
			$settings = json_decode($settings["preset"], true);
			if($settings === false) {
				return;
			}
			$levelName = $event->getLevel()->getFolderName();
			$default = array_filter($this->plugin->getConfig()->get("DefaultWorld", []), function($key){
				return !in_array($key, ["PlotSize", "GroundHeight", "RoadWidth", "RoadBlock", "WallBlock", "PlotFloorBlock", "PlotFillBlock", "BottomBlock"]);
			}, ARRAY_FILTER_USE_KEY);
			$config = new Config($this->plugin->getDataFolder()."worlds".DIRECTORY_SEPARATOR.$levelName.".yml", Config::YAML, $default);
			foreach(array_keys($default) as $key) {
				$settings[$key] = $config->get((string)$key);
			}
			$this->plugin->addLevelSettings($levelName, new PlotLevelSettings($levelName, $settings));
		}
	}

	/**
	 * @ignoreCancelled false
	 * @priority MONITOR
	 *
	 * @param LevelUnloadEvent $event
	 */
	public function onLevelUnload(LevelUnloadEvent $event) : void {
		if($event->isCancelled()) {
			return;
		}
		$levelName = $event->getLevel()->getFolderName();
		if($this->plugin->unloadLevelSettings($levelName)) {
			$this->plugin->getLogger()->debug("Level " . $event->getLevel()->getFolderName() . " unloaded!");
		}
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param BlockPlaceEvent $event
	 */
	public function onBlockPlace(BlockPlaceEvent $event) : void {
		$this->onEventOnBlock($event);
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param BlockBreakEvent $event
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void {
		$this->onEventOnBlock($event);
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param PlayerInteractEvent $event
	 */
	public function onPlayerInteract(PlayerInteractEvent $event) : void {
		$this->onEventOnBlock($event);
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param SignChangeEvent $event
	 */
	public function onSignChange(SignChangeEvent $event) : void {
		$this->onEventOnBlock($event);
	}

	/**
	 * @param BlockPlaceEvent|BlockBreakEvent|PlayerInteractEvent|SignChangeEvent $event
	 */
	private function onEventOnBlock($event) : void {
		if(!$event->getBlock()->isValid())
			return;
		$levelName = $event->getBlock()->getLevel()->getFolderName();
		if(!$this->plugin->isLevelLoaded($levelName)) {
			return;
		}
		$plot = $this->plugin->getPlotByPosition($event->getBlock());
		if($plot !== null) {
			$ev = new MyPlotBlockEvent($plot, $event->getBlock(), $event->getPlayer(), $event);
			if($event->isCancelled()) {
				$ev->setCancelled($event->isCancelled());
			}
			$ev->call();
			$event->setCancelled($ev->isCancelled());
			$username = $event->getPlayer()->getName();
			if($plot->owner == $username or $plot->isHelper($username) or $plot->isHelper("*") or $event->getPlayer()->hasPermission("myplot.admin.build.plot")) {
				if(!($event instanceof PlayerInteractEvent and $event->getBlock() instanceof Sapling))
					return;
				/*
				 * Prevent growing a tree near the edge of a plot
				 * so the leaves won't go outside the plot
				 */
				$block = $event->getBlock();
				$maxLengthLeaves = (($block->getDamage() & 0x07) == Sapling::SPRUCE) ? 3 : 2;
				$beginPos = $this->plugin->getPlotPosition($plot);
				$endPos = clone $beginPos;
				$beginPos->x += $maxLengthLeaves;
				$beginPos->z += $maxLengthLeaves;
				$plotSize = $this->plugin->getLevelSettings($levelName)->plotSize;
				$endPos->x += $plotSize - $maxLengthLeaves;
				$endPos->z += $plotSize - $maxLengthLeaves;
				if($block->x >= $beginPos->x and $block->z >= $beginPos->z and $block->x < $endPos->x and $block->z < $endPos->z) {
					return;
				}
			}
		}elseif($event->getPlayer()->hasPermission("myplot.admin.build.road"))
			return;
		elseif($this->plugin->isPositionBorderingPlot($event->getBlock()) and $this->plugin->getLevelSettings($levelName)->editBorderBlocks) {
			$plot = $this->plugin->getPlotBorderingPosition($event->getBlock());
			if($plot instanceof Plot) {
				$ev = new MyPlotBorderChangeEvent($plot, $event->getBlock(), $event->getPlayer(), $event);
				if($event->isCancelled()) {
					$ev->setCancelled($event->isCancelled());
				}
				$ev->call();
				$event->setCancelled($ev->isCancelled());
				$username = $event->getPlayer()->getName();
				if($plot->owner == $username or $plot->isHelper($username) or $plot->isHelper("*") or $event->getPlayer()->hasPermission("myplot.admin.build.plot"))
					if(!($event instanceof PlayerInteractEvent and $event->getBlock() instanceof Sapling))
						return;
			}
		}
		$event->setCancelled();
		$this->plugin->getLogger()->debug("Block placement/break/interaction of {$event->getBlock()->getName()} was cancelled at ".$event->getBlock()->asPosition()->__toString());
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param EntityExplodeEvent $event
	 */
	public function onExplosion(EntityExplodeEvent $event) : void {
		if($event->isCancelled()) {
			return;
		}
		$levelName = $event->getEntity()->getLevel()->getFolderName();
		if(!$this->plugin->isLevelLoaded($levelName))
			return;
		$plot = $this->plugin->getPlotByPosition($event->getPosition());
		if($plot === null) {
			$event->setCancelled();
			return;
		}
		$beginPos = $this->plugin->getPlotPosition($plot);
		$endPos = clone $beginPos;
		$plotSize = $this->plugin->getLevelSettings($levelName)->plotSize;
		$endPos->x += $plotSize;
		$endPos->z += $plotSize;
		$blocks = array_filter($event->getBlockList(), function($block) use ($beginPos, $endPos) {
			if($block->x >= $beginPos->x and $block->z >= $beginPos->z and $block->x < $endPos->x and $block->z < $endPos->z) {
				return true;
			}
			return false;
		});
		$event->setBlockList($blocks);
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param EntityMotionEvent $event
	 */
	public function onEntityMotion(EntityMotionEvent $event) : void {
		if($event->isCancelled()) {
			return;
		}
		$level = $event->getEntity()->getLevel();
		if(!$level instanceof Level)
			return;
		$levelName = $level->getFolderName();
		if(!$this->plugin->isLevelLoaded($levelName))
			return;
		$settings = $this->plugin->getLevelSettings($levelName);
		if($settings->restrictEntityMovement and !($event->getEntity() instanceof Player)) {
			$event->setCancelled();
			$this->plugin->getLogger()->debug("Cancelled entity motion on " . $levelName);
		}
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param BlockSpreadEvent $event
	 */
	public function onBlockSpread(BlockSpreadEvent $event) : void {
		if($event->isCancelled()) {
			return;
		}
		$levelName = $event->getBlock()->getLevel()->getFolderName();
		if(!$this->plugin->isLevelLoaded($levelName))
			return;
		$settings = $this->plugin->getLevelSettings($levelName);
		if(!$settings->updatePlotLiquids) {
			$event->setCancelled();
			$this->plugin->getLogger()->debug("Cancelled block spread of {$event->getBlock()->getName()} on " . $levelName);
		}
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param PlayerMoveEvent $event
	 */
	public function onPlayerMove(PlayerMoveEvent $event) : void {
		$levelName = $event->getPlayer()->getLevel()->getFolderName();
		if(!$this->plugin->isLevelLoaded($levelName))
			return;
		$plot = $this->plugin->getPlotByPosition($event->getTo());
		$plotFrom = $this->plugin->getPlotByPosition($event->getFrom());
		if($plot !== null and ($plotFrom === null or !$plot->isSame($plotFrom))) {
			if(strpos((string) $plot, "-0")) {
				return;
			}
			$ev = new MyPlotPlayerEnterPlotEvent($plot, $event->getPlayer());
			$ev->setCancelled($event->isCancelled());
			$username = $event->getPlayer()->getName();
			if($plot->owner !== $username and ($plot->isDenied($username) or $plot->isDenied("*")) and !$event->getPlayer()->hasPermission("myplot.admin.denyplayer.bypass")) {
				$ev->setCancelled();
			}
			$ev->call();
			$event->setCancelled($ev->isCancelled());
			if($event->isCancelled()) {
				return;
			}
			if(!$this->plugin->getConfig()->get("ShowPlotPopup", true))
				return;
			$popup = $this->plugin->getLanguage()->translateString("popup", [TextFormat::GREEN . $plot]);
			if(!empty($plot->owner)) {
				$owner = TextFormat::GREEN . $plot->owner;
				$ownerPopup = $this->plugin->getLanguage()->translateString("popup.owner", [$owner]);
			}else{
				$ownerPopup = $this->plugin->getLanguage()->translateString("popup.available");
			}
			$paddingSize = (int) floor((strlen($popup) - strlen($ownerPopup)) / 2);
			$paddingPopup = str_repeat(" ", max(0, -$paddingSize));
			$paddingOwnerPopup = str_repeat(" ", max(0, $paddingSize));
			$popup = TextFormat::WHITE . $paddingPopup . $popup . "\n" . TextFormat::WHITE . $paddingOwnerPopup . $ownerPopup;
			$event->getPlayer()->sendTip($popup);
		}elseif($plotFrom !== null and ($plot === null or !$plot->isSame($plotFrom))) {
			if(strpos((string) $plotFrom, "-0")) {
				return;
			}
			$ev = new MyPlotPlayerLeavePlotEvent($plotFrom, $event->getPlayer());
			$ev->setCancelled($event->isCancelled());
			$ev->call();
			$event->setCancelled($ev->isCancelled());
		}
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param EntityDamageByEntityEvent $event
	 */
	public function onEntityDamage(EntityDamageByEntityEvent $event) : void {
		$damaged = $event->getEntity();
		$damager = $event->getDamager();
		if($damaged instanceof Player and $damager instanceof Player and !$event->isCancelled()) {
			$levelName = $damaged->getLevel()->getFolderName();
			if(!$this->plugin->isLevelLoaded($levelName)) {
				return;
			}
			$settings = $this->plugin->getLevelSettings($levelName);
			$plot = $this->plugin->getPlotByPosition($damaged);
			if($plot !== null) {
				$ev = new MyPlotPvpEvent($plot, $damager, $damaged, $event);
				if(!$plot->pvp and !$damager->hasPermission("myplot.admin.pvp.bypass")) {
					$ev->setCancelled();
					$this->plugin->getLogger()->debug("Cancelled pvp event in plot ".$plot->X.";".$plot->Z." on level '" . $levelName . "'");
				}
				$ev->call();
				$event->setCancelled($ev->isCancelled());
				if($event->isCancelled()) {
					$ev->getAttacker()->sendMessage(TextFormat::RED . $this->plugin->getLanguage()->translateString("pvp.disabled")); // generic message- we dont know if by config or plot
				}
				return;
			}
			if($damager->hasPermission("myplot.admin.pvp.bypass")) {
				return;
			}
			if($settings->restrictPVP) {
				$event->setCancelled();
				$damager->sendMessage(TextFormat::RED.$this->plugin->getLanguage()->translateString("pvp.world"));
				$this->plugin->getLogger()->debug("Cancelled pvp event on ".$levelName);
			}
		}
	}
}
