<?php
declare(strict_types=1);
namespace MyPlot;

use MyPlot\events\MyPlotBlockEvent;
use MyPlot\events\MyPlotBorderChangeEvent;
use MyPlot\events\MyPlotPlayerEnterPlotEvent;
use MyPlot\events\MyPlotPlayerLeavePlotEvent;
use MyPlot\events\MyPlotPvpEvent;
use MyPlot\plot\BasePlot;
use MyPlot\plot\SinglePlot;
use pocketmine\block\Liquid;
use pocketmine\block\Sapling;
use pocketmine\block\utils\TreeType;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

final class EventListener implements Listener{
	public function __construct(private MyPlot $plugin, private InternalAPI $internalAPI){ }

	/**
	 * @priority LOWEST
	 *
	 * @param WorldLoadEvent $event
	 */
	public function onLevelLoad(WorldLoadEvent $event) : void{
		if(file_exists($this->plugin->getDataFolder() . "worlds" . DIRECTORY_SEPARATOR . $event->getWorld()->getFolderName() . ".yml")){
			$this->plugin->getLogger()->debug("MyPlot level " . $event->getWorld()->getFolderName() . " loaded!");
			$settings = $event->getWorld()->getProvider()->getWorldData()->getGeneratorOptions();
			$settings = json_decode($settings, true);
			if($settings === false){
				return;
			}
			$levelName = $event->getWorld()->getFolderName();
			$default = array_filter((array) $this->plugin->getConfig()->get("DefaultWorld", []), function($key) : bool{
				return !in_array($key, ["PlotSize", "GroundHeight", "RoadWidth", "RoadBlock", "WallBlock", "PlotFloorBlock", "PlotFillBlock", "BottomBlock"], true);
			}, ARRAY_FILTER_USE_KEY);
			$config = new Config($this->plugin->getDataFolder() . "worlds" . DIRECTORY_SEPARATOR . $levelName . ".yml", Config::YAML, $default);
			foreach(array_keys($default) as $key){
				$settings[$key] = $config->get((string) $key);
			}
			$this->internalAPI->addLevelSettings($levelName, new PlotLevelSettings($levelName, $settings));

			if($this->plugin->getConfig()->get('AllowFireTicking', false) === false){
				$ref = new \ReflectionClass(World::class);
				$prop = $ref->getProperty('randomTickBlocks');
				$prop->setAccessible(true);
				$randomTickBlocks = $prop->getValue($event->getWorld());
				unset($randomTickBlocks[VanillaBlocks::FIRE()->getFullId()]);
				$prop->setValue($event->getWorld(), $randomTickBlocks);
			}
		}
	}

	/**
	 * @priority MONITOR
	 *
	 * @param WorldUnloadEvent $event
	 */
	public function onLevelUnload(WorldUnloadEvent $event) : void{
		$levelName = $event->getWorld()->getFolderName();
		if($this->internalAPI->unloadLevelSettings($levelName)){
			$this->plugin->getLogger()->debug("Level " . $event->getWorld()->getFolderName() . " unloaded!");
		}
	}

	/**
	 * @handleCancelled true
	 * @priority LOWEST
	 *
	 * @param BlockPlaceEvent $event
	 */
	public function onBlockPlace(BlockPlaceEvent $event) : void{
		$this->onEventOnBlock($event);
	}

	/**
	 * @handleCancelled true
	 * @priority LOWEST
	 *
	 * @param BlockBreakEvent $event
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		$this->onEventOnBlock($event);
	}

	/**
	 * @handleCancelled true
	 * @priority LOWEST
	 *
	 * @param PlayerInteractEvent $event
	 */
	public function onPlayerInteract(PlayerInteractEvent $event) : void{
		$this->onEventOnBlock($event);
	}

	/**
	 * @handleCancelled true
	 * @priority LOWEST
	 *
	 * @param SignChangeEvent $event
	 */
	public function onSignChange(SignChangeEvent $event) : void{
		$this->onEventOnBlock($event);
	}

	private function onEventOnBlock(BlockPlaceEvent|SignChangeEvent|PlayerInteractEvent|BlockBreakEvent $event) : void{
		$levelName = $event->getBlock()->getPosition()->getWorld()->getFolderName();
		$plotLevel = $this->internalAPI->getLevelSettings($levelName);
		if($plotLevel === null)
			return;

		$pos = $event->getBlock()->getPosition();
		$x = $pos->x;
		$z = $pos->z;
		$plot = $this->internalAPI->getPlotFast($x, $z, $plotLevel);

		if($plot !== null){
			$ev = new MyPlotBlockEvent($plot, $event->getBlock(), $event->getPlayer(), $event);
			if($event->isCancelled())
				$ev->cancel();

			$ev->call();
			$ev->isCancelled() ? $event->cancel() : $event->uncancel();
			$plot = $this->internalAPI->getPlotFromCache($plot, true);
			if(!$plot instanceof SinglePlot){
				$event->cancel();
				$this->plugin->getLogger()->debug("Cancelled block change event at $x,$pos->y,$z in [$levelName]");
				return;
			}
			$username = $event->getPlayer()->getName();
			if($plot->owner == $username or $plot->isHelper($username) or $plot->isHelper("*") or $event->getPlayer()->hasPermission("myplot.admin.build.plot")){
				if(!($event instanceof PlayerInteractEvent and $event->getBlock() instanceof Sapling))
					return;
				/*
				 * Prevent growing a tree near the edge of a plot
				 * so the leaves won't go outside the plot
				 */
				$block = $event->getBlock();
				$maxLengthLeaves = $block->getIdInfo()->getVariant() === TreeType::SPRUCE()->getMagicNumber() ? 3 : 2;
				$beginPos = $this->internalAPI->getPlotPosition($plot);
				$endPos = clone $beginPos;
				$beginPos->x += $maxLengthLeaves;
				$beginPos->z += $maxLengthLeaves;
				$plotSize = $plotLevel->plotSize;
				$endPos->x += $plotSize - $maxLengthLeaves;
				$endPos->z += $plotSize - $maxLengthLeaves;
				if($block->getPosition()->x >= $beginPos->x and $block->getPosition()->z >= $beginPos->z and $block->getPosition()->x < $endPos->x and $block->getPosition()->z < $endPos->z)
					return;
			}
		}elseif($event->getPlayer()->hasPermission("myplot.admin.build.road"))
			return;
		elseif($plotLevel->editBorderBlocks){
			$plot = $this->internalAPI->getPlotBorderingPosition($event->getBlock()->getPosition());
			if($plot !== null){
				$plot = $this->internalAPI->getPlotFromCache($plot, true);
				if(!$plot instanceof SinglePlot){
					$event->cancel();
					$this->plugin->getLogger()->debug("Cancelled block change event at $x,$pos->y,$z in [$levelName]");
					return;
				}

				$ev = new MyPlotBorderChangeEvent($plot, $event->getBlock(), $event->getPlayer(), $event);
				if($event->isCancelled())
					$ev->cancel();

				$ev->call();
				$ev->isCancelled() ? $event->cancel() : $event->uncancel();

				$username = $event->getPlayer()->getName();
				if($plot->owner == $username or $plot->isHelper($username) or $plot->isHelper("*") or $event->getPlayer()->hasPermission("myplot.admin.build.plot"))
					if(!($event instanceof PlayerInteractEvent and $event->getBlock() instanceof Sapling))
						return;
			}
		}
		$event->cancel();
		$this->plugin->getLogger()->debug("Block placement/break/interaction of {$event->getBlock()->getName()} was cancelled at " . $event->getBlock()->getPosition()->__toString());
	}

	/**
	 * @priority LOWEST
	 *
	 * @param EntityExplodeEvent $event
	 */
	public function onExplosion(EntityExplodeEvent $event) : void{
		$levelName = $event->getEntity()->getWorld()->getFolderName();
		$plotLevel = $this->internalAPI->getLevelSettings($levelName);
		if($plotLevel === null)
			return;

		$pos = $event->getPosition();
		$x = $pos->x;
		$z = $pos->z;

		$plot = $this->internalAPI->getPlotFast($x, $z, $plotLevel);
		if($plot === null){
			$event->cancel();
			return;
		}
		$beginPos = $this->internalAPI->getPlotPosition($plot);
		$endPos = clone $beginPos;
		$plotSize = $plotLevel->plotSize;
		$endPos->x += $plotSize;
		$endPos->z += $plotSize;
		$blocks = array_filter($event->getBlockList(), function($block) use ($beginPos, $endPos) : bool{
			if($block->getPosition()->x >= $beginPos->x and $block->getPosition()->z >= $beginPos->z and $block->getPosition()->x < $endPos->x and $block->getPosition()->z < $endPos->z){
				return true;
			}
			return false;
		});
		$event->setBlockList($blocks);
	}

	/**
	 * @priority LOWEST
	 *
	 * @param EntityMotionEvent $event
	 */
	public function onEntityMotion(EntityMotionEvent $event) : void{
		$level = $event->getEntity()->getWorld();
		if(!$level instanceof World)
			return;
		$levelName = $level->getFolderName();
		if($this->internalAPI->getLevelSettings($levelName) === null)
			return;
		$settings = $this->internalAPI->getLevelSettings($levelName);
		if($settings->restrictEntityMovement and !($event->getEntity() instanceof Player)){
			$event->cancel();
			$this->plugin->getLogger()->debug("Cancelled entity motion on " . $levelName);
		}
	}

	/**
	 * @priority LOWEST
	 *
	 * @param BlockSpreadEvent $event
	 */
	public function onBlockSpread(BlockSpreadEvent $event) : void{
		$levelName = $event->getBlock()->getPosition()->getWorld()->getFolderName();
		$plotLevel = $this->internalAPI->getLevelSettings($levelName);
		if($plotLevel === null)
			return;

		$pos = $event->getBlock()->getPosition();
		$x = $pos->x;
		$z = $pos->z;
		$newBlockInPlot = ($plotA = $this->internalAPI->getPlotFast($x, $z, $plotLevel)) !== null;

		$pos = $event->getSource()->getPosition();
		$x = $pos->x;
		$z = $pos->z;
		$sourceBlockInPlot = ($plotB = $this->internalAPI->getPlotFast($x, $z, $plotLevel)) !== null;

		$spreadIsSamePlot = (($newBlockInPlot and $sourceBlockInPlot)) and $plotA->isSame($plotB);

		if($event->getSource() instanceof Liquid and (!$plotLevel->updatePlotLiquids or !$spreadIsSamePlot)){
			$event->cancel();
			$this->plugin->getLogger()->debug("Cancelled {$event->getSource()->getName()} spread on [$levelName]");
		}elseif(!$plotLevel->allowOutsidePlotSpread and (!$newBlockInPlot or !$spreadIsSamePlot)){
			$event->cancel();
			//$this->plugin->getLogger()->debug("Cancelled block spread of {$event->getSource()->getName()} on ".$levelName);
		}
	}

	/**
	 * @handleCancelled true
	 * @priority LOWEST
	 *
	 * @param PlayerMoveEvent $event
	 */
	public function onPlayerMove(PlayerMoveEvent $event) : void{
		$this->onEventOnMove($event->getPlayer(), $event);
	}

	/**
	 * @handleCancelled true
	 * @priority LOWEST
	 *
	 * @param EntityTeleportEvent $event
	 */
	public function onPlayerTeleport(EntityTeleportEvent $event) : void{
		$entity = $event->getEntity();
		if($entity instanceof Player){
			$this->onEventOnMove($entity, $event);
		}
	}

	private function onEventOnMove(Player $player, EntityTeleportEvent|PlayerMoveEvent $event) : void{
		$levelName = $player->getWorld()->getFolderName();
		$plotLevel = $this->internalAPI->getLevelSettings($levelName);
		if($plotLevel === null)
			return;

		$pos = $event->getTo();
		$x = $pos->x;
		$z = $pos->z;
		$plot = $this->internalAPI->getPlotFast($x, $z, $plotLevel);

		$pos = $event->getFrom();
		$x = $pos->x;
		$z = $pos->z;
		$plotFrom = $this->internalAPI->getPlotFast($x, $z, $plotLevel);

		if($plotFrom !== null and ($plot === null or !$plot->isSame($plotFrom))){
			if(str_contains((string) $plotFrom, "-0"))
				return;

			$ev = new MyPlotPlayerLeavePlotEvent($plotFrom, $player);
			$event->isCancelled() ? $ev->cancel() : $ev->uncancel();
			$ev->call();
			$ev->isCancelled() ? $event->cancel() : $event->uncancel();
		}

		if($plot !== null)
			$plot = $this->internalAPI->getPlotFromCache($plot, true);

		if($plot instanceof BasePlot and ($plotFrom === null or !$plot->isSame($plotFrom))){
			if(str_contains((string) $plot, "-0"))
				return;
			$plot = SinglePlot::fromBase($plot);

			$ev = new MyPlotPlayerEnterPlotEvent($plot, $player);
			$event->isCancelled() ? $ev->cancel() : $ev->uncancel();

			$username = $ev->getPlayer()->getName();
			if($plot->owner !== $username and
				($plot->isDenied($username) or $plot->isDenied("*")) and
				!$ev->getPlayer()->hasPermission("myplot.admin.denyplayer.bypass")
			)
				$ev->cancel();

			$ev->call();
			$ev->isCancelled() ? $event->cancel() : $event->uncancel();
			if($event->isCancelled()){
				$this->internalAPI->teleportPlayerToPlot($player, $plot);
				return;
			}

			if($this->plugin->getConfig()->get("ShowPlotPopup", true) === false)
				return;

			$popup = $this->plugin->getLanguage()->translateString("popup", [TextFormat::GREEN . $plot]);
			$price = TextFormat::GREEN . $plot->price;
			if($plot->owner !== ""){
				$owner = TextFormat::GREEN . $plot->owner;
				if($plot->price > 0 and $plot->owner !== $player->getName()){
					$ownerPopup = $this->plugin->getLanguage()->translateString("popup.forsale", [$owner . TextFormat::WHITE, $price . TextFormat::WHITE]);
				}else{
					$ownerPopup = $this->plugin->getLanguage()->translateString("popup.owner", [$owner . TextFormat::WHITE]);
				}
			}else{
				$ownerPopup = $this->plugin->getLanguage()->translateString("popup.available", [$price . TextFormat::WHITE]);
			}
			$paddingSize = (int) floor((strlen($popup) - strlen($ownerPopup)) / 2);
			$paddingPopup = str_repeat(" ", max(0, -$paddingSize));
			$paddingOwnerPopup = str_repeat(" ", max(0, $paddingSize));
			$popup = TextFormat::WHITE . $paddingPopup . $popup . "\n" . TextFormat::WHITE . $paddingOwnerPopup . $ownerPopup;
			$ev->getPlayer()->sendTip($popup);
		}
	}

	/**
	 * @priority LOWEST
	 *
	 * @param EntityDamageByEntityEvent $event
	 */
	public function onEntityDamage(EntityDamageByEntityEvent $event) : void{
		$damaged = $event->getEntity();
		$damager = $event->getDamager();
		if($damaged instanceof Player and $damager instanceof Player){
			$levelName = $damaged->getWorld()->getFolderName();
			$plotLevel = $this->internalAPI->getLevelSettings($levelName);
			if($plotLevel === null)
				return;

			$pos = $damaged->getPosition();
			$x = $pos->x;
			$z = $pos->z;
			$plot = $this->internalAPI->getPlotFast($x, $z, $plotLevel);
			if($plot !== null){
				$plot = $this->internalAPI->getPlotFromCache($plot);
				if(!$plot instanceof SinglePlot){
					if($plotLevel->restrictPVP and !$damager->hasPermission("myplot.admin.pvp.bypass"))
						$event->cancel();
					$this->plugin->getLogger()->debug("Cancelled player damage on [$levelName] due to plot not cached");
					return;
				}

				$ev = new MyPlotPvpEvent($plot, $damager, $damaged, $event);
				if(!$plot->pvp and !$damager->hasPermission("myplot.admin.pvp.bypass")){
					$ev->cancel();
					$this->plugin->getLogger()->debug("Cancelled pvp event in plot " . $plot->X . ";" . $plot->Z . " on level '" . $levelName . "'");
				}
				$ev->call();
				$ev->isCancelled() ? $event->cancel() : $event->uncancel();
				if($event->isCancelled()){
					$ev->getAttacker()->sendMessage(TextFormat::RED . $this->plugin->getLanguage()->translateString("pvp.disabled")); // generic message- we dont know if by config or plot
				}
				return;
			}

			if($plotLevel->restrictPVP and !$damager->hasPermission("myplot.admin.pvp.bypass")){
				$event->cancel();
				$damager->sendMessage(TextFormat::RED . $this->plugin->getLanguage()->translateString("pvp.world"));
				$this->plugin->getLogger()->debug("Cancelled pvp event on " . $levelName);
			}
		}
	}
}
