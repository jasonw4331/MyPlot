<?php
declare(strict_types=1);
namespace MyPlot;

use MyPlot\events\MyPlotBlockEvent;
use MyPlot\events\MyPlotBorderChangeEvent;
use MyPlot\events\MyPlotPlayerEnterPlotEvent;
use MyPlot\events\MyPlotPlayerLeavePlotEvent;
use MyPlot\events\MyPlotPvpEvent;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\BlockLegacyIds as BlockIds;
use pocketmine\block\Liquid;
use pocketmine\block\Sapling;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\VanillaItems;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\World as Level;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class EventListener implements Listener
{
	private MyPlot $plugin;

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
	 * @param WorldLoadEvent $event
	 *
	 * @throws \ReflectionException
	 */
	public function onLevelLoad(WorldLoadEvent $event) : void {
        $world = $event->getWorld();
		if(file_exists($this->plugin->getDataFolder()."worlds".DIRECTORY_SEPARATOR.$event->getWorld()->getFolderName().".yml")) {
			$this->plugin->getLogger()->debug("MyPlot level " . $world->getFolderName() . " loaded!");
			$options = $world->getProvider()->getWorldData()->getGeneratorOptions();
			$settings = json_decode($options, true, 512, JSON_THROW_ON_ERROR);
			$levelName = $world->getFolderName();
			$default = array_filter((array) $this->plugin->getConfig()->get("DefaultWorld", []), function($key) : bool {
				return !in_array($key, ["PlotSize", "GroundHeight", "RoadWidth", "RoadBlock", "WallBlock", "PlotFloorBlock", "PlotFillBlock", "BottomBlock"], true);
			}, ARRAY_FILTER_USE_KEY);
			$config = new Config($this->plugin->getDataFolder() . "worlds" . DIRECTORY_SEPARATOR . $levelName . ".yml", Config::YAML, $default);
			foreach(array_keys($default) as $key){
				$settings[$key] = $config->get((string)$key);
			}
			$this->plugin->addLevelSettings($levelName, new PlotLevelSettings($levelName, $settings));

			/*if($this->plugin->getConfig()->get('AllowFireTicking', false) === false) {
				$ref = new \ReflectionClass($event->getWorld());
				$prop = $ref->getProperty('randomTickBlocks');
				$prop->setAccessible(true);
				/** @var \SplFixedArray<Block|null> $randomTickBlocks */
				/*$randomTickBlocks = $prop->getValue($event->getWorld());
				$randomTickBlocks->offsetUnset(BlockIds::FIRE);
				$prop->setValue($randomTickBlocks, $event->getWorld());
			}*/
		}
	}

	/**
	 * @ignoreCancelled false
	 * @priority MONITOR
	 *
	 * @param WorldUnloadEvent $event
	 */
	public function onLevelUnload(WorldUnloadEvent $event) : void {
		if($event->isCancelled()) {
			return;
		}
		$levelName = $event->getWorld()->getFolderName();
		if($this->plugin->unloadLevelSettings($levelName)) {
			$this->plugin->getLogger()->debug("Level " . $event->getWorld()->getFolderName() . " unloaded!");
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
        $blockId = $event->getBlock()->getId();
        $itemId = $event->getItem()->getId();

        $blockedBlockIds = [
            VanillaBlocks::CHEST()->getId(),
            VanillaBlocks::ENDER_CHEST()->getId(),
            VanillaBlocks::TRAPPED_CHEST()->getId(),
            VanillaBlocks::FURNACE()->getId(),
            VanillaBlocks::BLAST_FURNACE()->getId(),
            VanillaBlocks::SMOKER()->getId(),
            VanillaBlocks::ITEM_FRAME()->getId(),
            VanillaBlocks::BREWING_STAND()->getId(),
            VanillaBlocks::ENCHANTING_TABLE()->getId(),
            VanillaBlocks::HOPPER()->getId(),
            VanillaBlocks::BARREL()->getId(),
            VanillaBlocks::IRON_TRAPDOOR()->getId(),
            VanillaBlocks::IRON_DOOR()->getId(),
            VanillaBlocks::OAK_FENCE_GATE()->getId(),
            VanillaBlocks::DARK_OAK_FENCE_GATE()->getId(),
            VanillaBlocks::BIRCH_FENCE_GATE()->getId(),
            VanillaBlocks::ACACIA_FENCE_GATE()->getId(),
            VanillaBlocks::SPRUCE_FENCE_GATE()->getId(),
            VanillaBlocks::SHULKER_BOX()->getId(),
            VanillaBlocks::DYED_SHULKER_BOX()->getId(),
            VanillaBlocks::BEACON()->getId(),
            VanillaBlocks::FLOWER_POT()->getId(),
            VanillaBlocks::BED()->getId(),
            VanillaBlocks::CAKE()->getId(),
            VanillaBlocks::JUKEBOX()->getId(),
            VanillaBlocks::NOTE_BLOCK()->getId(),
            VanillaBlocks::PUMPKIN()->getId(),
            VanillaBlocks::DAYLIGHT_SENSOR()->getId(),
            VanillaBlocks::TNT()->getId(),
            VanillaBlocks::REDSTONE_REPEATER()->getId(),
            VanillaBlocks::REDSTONE_COMPARATOR()->getId(),
            VanillaBlocks::DRAGON_EGG()->getId()
        ];

        $blockedItemIds = [
            VanillaItems::SHEARS()->getId(),
            VanillaItems::FLINT_AND_STEEL()->getId(),
            VanillaItems::BUCKET()->getId(),
            VanillaItems::MILK_BUCKET()->getId(),
            VanillaItems::LAVA_BUCKET()->getId(),
            VanillaItems::WATER_BUCKET()->getId()
        ];

        if ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK && !in_array($blockId, $blockedBlockIds) && !in_array($itemId, $blockedItemIds))
            return;
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

	private function onEventOnBlock(BlockPlaceEvent|SignChangeEvent|PlayerInteractEvent|BlockBreakEvent $event) : void {
		if(!$event->getBlock()->getPosition()->isValid())
			return;
		$levelName = $event->getBlock()->getPosition()->getWorld()->getFolderName();
		if(!$this->plugin->isLevelLoaded($levelName)) {
			return;
		}
		$plot = $this->plugin->getPlotByPosition($event->getBlock()->getPosition());
		if($plot !== null) {
			$ev = new MyPlotBlockEvent($plot, $event->getBlock(), $event->getPlayer(), $event);
			if($event->isCancelled()) {
				$ev->cancel();
			} else {
                $ev->uncancel();
            }
			$ev->call();
            if($event->isCancelled()) {
				$ev->cancel();
			} else {
                $ev->uncancel();
            }
			$username = $event->getPlayer()->getName();
			if($plot->owner == $username or $plot->isHelper($username) or $plot->isHelper("*") or $event->getPlayer()->hasPermission("myplot.admin.build.plot")) {
				if(!($event instanceof PlayerInteractEvent and $event->getBlock() instanceof Sapling))
					return;
				/*
				 * Prevent growing a tree near the edge of a plot
				 * so the leaves won't go outside the plot
				 */
				$block = $event->getBlock();
				$maxLengthLeaves = (($block->getMeta() & 0x07) == VanillaBlocks::SPRUCE_SAPLING()->getMeta()) ? 3 : 2;
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
		elseif($this->plugin->isPositionBorderingPlot($event->getBlock()->getPosition()) and $this->plugin->getLevelSettings($levelName)->editBorderBlocks) {
			$plot = $this->plugin->getPlotBorderingPosition($event->getBlock()->getPosition());
			if($plot instanceof Plot) {
				$ev = new MyPlotBorderChangeEvent($plot, $event->getBlock(), $event->getPlayer(), $event);
				if($event->isCancelled()) {
                    $ev->cancel();
                } else {
                    $ev->uncancel();
                }
                $ev->call();
                if($event->isCancelled()) {
                    $ev->cancel();
                } else {
                    $ev->uncancel();
                }
				$username = $event->getPlayer()->getName();
				if($plot->owner == $username or $plot->isHelper($username) or $plot->isHelper("*") or $event->getPlayer()->hasPermission("myplot.admin.build.plot"))
					if(!($event instanceof PlayerInteractEvent and $event->getBlock() instanceof Sapling))
						return;
			}
		}
		$event->cancel();
		$this->plugin->getLogger()->debug("Block placement/break/interaction of {$event->getBlock()->getName()} was cancelled at ".$event->getBlock()->getPosition()->__toString());
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
		$levelName = $event->getEntity()->getWorld()->getFolderName();
		if(!$this->plugin->isLevelLoaded($levelName))
			return;
		$plot = $this->plugin->getPlotByPosition($event->getPosition());
		if($plot === null) {
			$event->cancel();
			return;
		}
		$beginPos = $this->plugin->getPlotPosition($plot);
		$endPos = clone $beginPos;
		$levelSettings = $this->plugin->getLevelSettings($levelName);
		$plotSize = $levelSettings->plotSize;
		$endPos->x += $plotSize;
		$endPos->z += $plotSize;
		$blocks = array_filter($event->getBlockList(), function($block) use ($beginPos, $endPos) : bool {
			if($block->getPosition()->x >= $beginPos->x and $block->getPosition()->z >= $beginPos->z and $block->getPosition()->x < $endPos->x and $block->getPosition()->z < $endPos->z) {
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
		$level = $event->getEntity()->getWorld();
		if(!$level instanceof Level)
			return;
		$levelName = $level->getFolderName();
		if(!$this->plugin->isLevelLoaded($levelName))
			return;
		$settings = $this->plugin->getLevelSettings($levelName);
		if($settings->restrictEntityMovement and !($event->getEntity() instanceof Player)) {
			$event->cancel();
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
		$levelName = $event->getBlock()->getPosition()->getWorld()->getFolderName();
		if(!$this->plugin->isLevelLoaded($levelName))
			return;
		$settings = $this->plugin->getLevelSettings($levelName);

		$newBlockInPlot = ($plotA = $this->plugin->getPlotByPosition($event->getBlock()->getPosition())) instanceof Plot;
		$sourceBlockInPlot = ($plotB = $this->plugin->getPlotByPosition($event->getSource()->getPosition())) instanceof Plot;

		$spreadIsSamePlot = (($newBlockInPlot and $sourceBlockInPlot)) && $plotA->isSame($plotB);

		if($event->getSource() instanceof Liquid) {
			if(!$settings->updatePlotLiquids and ($sourceBlockInPlot or $this->plugin->isPositionBorderingPlot($event->getSource()->getPosition()))) {
				$event->cancel();
				$this->plugin->getLogger()->debug("Cancelled {$event->getSource()->getName()} spread on [$levelName]");
			}elseif($settings->updatePlotLiquids and ($sourceBlockInPlot or $this->plugin->isPositionBorderingPlot($event->getSource()->getPosition())) and (!$newBlockInPlot or !$this->plugin->isPositionBorderingPlot($event->getBlock()->getPosition()) or !$spreadIsSamePlot)) {
				$event->cancel();
				$this->plugin->getLogger()->debug("Cancelled {$event->getSource()->getName()} spread on [$levelName]");
			}
		}elseif(!$settings->allowOutsidePlotSpread and (!$newBlockInPlot or !$spreadIsSamePlot)) {
			$event->cancel();
			//$this->plugin->getLogger()->debug("Cancelled block spread of {$event->getSource()->getName()} on ".$levelName);
		}
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param PlayerMoveEvent $event
	 */
	public function onPlayerMove(PlayerMoveEvent $event) : void {
		$this->onEventOnMove($event->getPlayer(), $event);
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param EntityTeleportEvent $event
	 */
	public function onPlayerTeleport(EntityTeleportEvent $event) : void {
		$entity = $event->getEntity();
		if ($entity instanceof Player) {
			$this->onEventOnMove($entity, $event);
		}
	}

	private function onEventOnMove(Player $player, EntityTeleportEvent|PlayerMoveEvent $event) : void {
		$levelName = $player->getWorld()->getFolderName();
		if (!$this->plugin->isLevelLoaded($levelName))
			return;
		$plot = $this->plugin->getPlotByPosition($event->getTo());
		$plotFrom = $this->plugin->getPlotByPosition($event->getFrom());
		if($plot !== null and ($plotFrom === null or !$plot->isSame($plotFrom))) {
			if(str_contains((string) $plot, "-0")) {
				return;
			}
			$ev = new MyPlotPlayerEnterPlotEvent($plot, $player);
			if($event->isCancelled()) {
				$ev->cancel();
			} else {
                $ev->uncancel();
            }
			$username = $ev->getPlayer()->getName();
			if($plot->owner !== $username and ($plot->isDenied($username) or $plot->isDenied("*")) and !$ev->getPlayer()->hasPermission("myplot.admin.denyplayer.bypass")) {
				$ev->cancel();
			}
			$ev->call();
            if($event->isCancelled()) {
				$ev->cancel();
			} else {
                $ev->uncancel();
            }
			if($event->isCancelled()) {
				return;
			}
			if(!(bool) $this->plugin->getConfig()->get("ShowPlotPopup", true))
				return;
			$popup = $this->plugin->getLanguage()->translateString("popup", [TextFormat::GREEN . $plot]);
			$price = TextFormat::GREEN . $plot->price;
			if($plot->owner !== "") {
				$owner = TextFormat::GREEN . $plot->owner;
				if($plot->price > 0 and $plot->owner !== $player->getName()) {
					$ownerPopup = $this->plugin->getLanguage()->translateString("popup.forsale", [$owner.TextFormat::WHITE, $price.TextFormat::WHITE]);
				}else{
					$ownerPopup = $this->plugin->getLanguage()->translateString("popup.owner", [$owner.TextFormat::WHITE]);
				}
			}else{
				$ownerPopup = $this->plugin->getLanguage()->translateString("popup.available", [$price.TextFormat::WHITE]);
			}
			$paddingSize = (int) floor((strlen($popup) - strlen($ownerPopup)) / 2);
			$paddingPopup = str_repeat(" ", max(0, -$paddingSize));
			$paddingOwnerPopup = str_repeat(" ", max(0, $paddingSize));
			$popup = TextFormat::WHITE . $paddingPopup . $popup . "\n" . TextFormat::WHITE . $paddingOwnerPopup . $ownerPopup;
			$ev->getPlayer()->sendTip($popup);
		}elseif($plotFrom !== null and ($plot === null or !$plot->isSame($plotFrom))) {
			if(str_contains((string) $plotFrom, "-0")) {
				return;
			}
			$ev = new MyPlotPlayerLeavePlotEvent($plotFrom, $player);
			if($event->isCancelled()) {
				$ev->cancel();
			} else {
                $ev->uncancel();
            }
			$ev->call();
            if($event->isCancelled()) {
				$ev->cancel();
			} else {
                $ev->uncancel();
            }
		}elseif($plotFrom !== null and $plot !== null and ($plot->isDenied($player->getName()) or $plot->isDenied("*")) and $plot->owner !== $player->getName() and !$player->hasPermission("myplot.admin.denyplayer.bypass")) {
			$this->plugin->teleportPlayerToPlot($player, $plot);
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
			$levelName = $damaged->getWorld()->getFolderName();
			if(!$this->plugin->isLevelLoaded($levelName)) {
				return;
			}
			$settings = $this->plugin->getLevelSettings($levelName);
			$plot = $this->plugin->getPlotByPosition($damaged->getPosition());
			if($plot !== null) {
				$ev = new MyPlotPvpEvent($plot, $damager, $damaged, $event);
				if(!$plot->pvp and !$damager->hasPermission("myplot.admin.pvp.bypass")) {
					$ev->cancel();
					$this->plugin->getLogger()->debug("Cancelled pvp event in plot ".$plot->X.";".$plot->Z." on level '" . $levelName . "'");
				}
				$ev->call();
                if($event->isCancelled()) {
                    $ev->cancel();
                } else {
                    $ev->uncancel();
                }
				if($event->isCancelled()) {
					$ev->getAttacker()->sendMessage(TextFormat::RED . $this->plugin->getLanguage()->translateString("pvp.disabled")); // generic message- we dont know if by config or plot
				}
				return;
			}
			if($damager->hasPermission("myplot.admin.pvp.bypass")) {
				return;
			}
			if($settings->restrictPVP) {
				$event->cancel();
				$damager->sendMessage(TextFormat::RED.$this->plugin->getLanguage()->translateString("pvp.world"));
				$this->plugin->getLogger()->debug("Cancelled pvp event on ".$levelName);
			}
		}
	}
}
