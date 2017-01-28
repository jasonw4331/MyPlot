<?php
namespace MyPlot;

use pocketmine\block\Liquid;
use pocketmine\block\Sapling;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\Player;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\Listener;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\level\LevelUnloadEvent;
use pocketmine\utils\Config;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\utils\TextFormat;

class EventListener implements Listener
{
    /** @var MyPlot */
    private $plugin;

    public function __construct(MyPlot $plugin){
        $this->plugin = $plugin;
    }

    public function onLevelLoad(LevelLoadEvent $event) {
        if ($event->getLevel()->getProvider()->getGenerator() == "myplot") {
	        $this->plugin->getLogger()->debug("MyPlot level".$event->getLevel()->getFolderName()." loaded!");
            $settings = $event->getLevel()->getProvider()->getGeneratorOptions();
            if (isset($settings["preset"]) === false or $settings["preset"] === "") {
                return;
            }
            $settings = json_decode($settings["preset"], true);
            if ($settings === false) {
                return;
            }
            $levelName = $event->getLevel()->getName();
            $filePath = $this->plugin->getDataFolder() . "worlds" . DIRECTORY_SEPARATOR . $levelName . ".yml";
            $config = $this->plugin->getConfig();
            $default = [
                "RestrictEntityMovement" => $config->getNested("DefaultWorld.RestrictEntityMovement"),
                "ClaimPrice" => $config->getNested("DefaultWorld.ClaimPrice"),
                "ClearPrice" => $config->getNested("DefaultWorld.ClearPrice"),
                "DisposePrice" => $config->getNested("DefaultWorld.DisposePrice"),
                "ResetPrice" => $config->getNested("DefaultWorld.ResetPrice")
            ];
            $config = new Config($filePath, Config::YAML, $default);
            foreach (array_keys($default) as $key) {
                $settings[$key] = $config->get($key);
            }
            $this->plugin->addLevelSettings($levelName, new PlotLevelSettings($levelName, $settings));
        }
    }

    public function onLevelUnload(LevelUnloadEvent $event) {
        $levelName = $event->getLevel()->getName();
        if($this->plugin->unloadLevelSettings($levelName)) {
	        $this->plugin->getLogger()->debug("Level ".$event->getLevel()->getFolderName()." unloaded!");
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event) {
        $this->onEventOnBlock($event);
    }

    public function onBlockBreak(BlockBreakEvent $event) {
        $this->onEventOnBlock($event);
    }

    public function onPlayerInteract(PlayerInteractEvent $event) {
        $this->onEventOnBlock($event);
    }

    public function onBlockUpdate(BlockUpdateEvent $event) {
        /*
         * Disables water and lava flow as a temporary solution.
         * TODO make a permanent fix
         */
        $levelName = $event->getBlock()->getLevel()->getName();
        if ($this->plugin->isLevelLoaded($levelName)) {
        	if($event->getBlock() instanceof Liquid) {
		        $event->setCancelled();
		        $this->plugin->getLogger()->debug("Block update cancelled in ".$levelName);
	        }
        }
    }

    public function onExplosion(EntityExplodeEvent $event) {
        $levelName = $event->getEntity()->getLevel()->getName();
        if (!$this->plugin->isLevelLoaded($levelName))
            return;

        $plot = $this->plugin->getPlotByPosition($event->getPosition());
        if ($plot === null) {
            $event->setCancelled();
            return;
        }
        $beginPos = $this->plugin->getPlotPosition($plot);
        $endPos = clone $beginPos;
        $plotSize = $this->plugin->getLevelSettings($levelName)->plotSize;
        $endPos->x += $plotSize;
        $endPos->z += $plotSize;
        $blocks = array_filter($event->getBlockList(), function($block) use($beginPos, $endPos) {
            if ($block->x >= $beginPos->x and $block->z >= $beginPos->z and $block->x < $endPos->x and $block->z < $endPos->z) {
                return true;
            }
            return false;
        });
        $event->setBlockList($blocks);
    }

    /**
     * @param BlockPlaceEvent|BlockBreakEvent|PlayerInteractEvent $event
     */
    private function onEventOnBlock($event) {
        $levelName = $event->getBlock()->getLevel()->getName();
        if (!$this->plugin->isLevelLoaded($levelName)) {
            return;
        }
        $plot = $this->plugin->getPlotByPosition($event->getBlock());
        if ($plot !== null) {
            $username = $event->getPlayer()->getName();
            if ($plot->owner == $username or $plot->isHelper($username) or $plot->isHelper("*") or $event->getPlayer()->hasPermission("myplot.admin.build.plot")) {
                if (!($event instanceof PlayerInteractEvent and $event->getBlock() instanceof Sapling))
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

                if ($block->x >= $beginPos->x and $block->z >= $beginPos->z and $block->x < $endPos->x and $block->z < $endPos->z) {
                    return;
                }
            }
        } elseif ($event->getPlayer()->hasPermission("myplot.admin.build.road")) {
            return;
        }
        $event->setCancelled();
	    $this->plugin->getLogger()->debug("Road block placement cancelled");
    }

    public function onEntityMotion(EntityMotionEvent $event) {
        $levelName = $event->getEntity()->getLevel()->getName();
        if (!$this->plugin->isLevelLoaded($levelName))
            return;

        $settings = $this->plugin->getLevelSettings($levelName);
        if ($settings->restrictEntityMovement and !($event->getEntity() instanceof Player)) {
            $event->setCancelled();
	        $this->plugin->getLogger()->debug("Cancelled entity motion on ".$levelName);
        }
    }

    public function onPlayerMove(PlayerMoveEvent $event) {
	    if (!$this->plugin->getConfig()->get("ShowPlotPopup", true))
            return;

        $levelName = $event->getPlayer()->getLevel()->getName();
        if (!$this->plugin->isLevelLoaded($levelName))
            return;

        $plot = $this->plugin->getPlotByPosition($event->getTo());
        if ($plot !== null and $plot !== $this->plugin->getPlotByPosition($event->getFrom())) {
	        if($plot->isDenied($event->getPlayer()->getName())) {
		        $event->setCancelled();
		        return;
	        }
            $plotName = TextFormat::GREEN . $plot;
            $popup = $this->plugin->getLanguage()->translateString("popup", [$plotName]);
	        if(strpos($plot,"-0")) {
		        return;
	        }
            if ($plot->owner != "") {
                $owner = TextFormat::GREEN . $plot->owner;
                $ownerPopup = $this->plugin->getLanguage()->translateString("popup.owner", [$owner]);
                $paddingSize = floor((strlen($popup) - strlen($ownerPopup)) / 2);
                $paddingPopup = str_repeat(" ", max(0, -$paddingSize));
                $paddingOwnerPopup = str_repeat(" ", max(0, $paddingSize));
	            $popup = TextFormat::WHITE . $paddingPopup . $popup . "\n" .
		            TextFormat::WHITE . $paddingOwnerPopup . $ownerPopup;
	            $this->plugin->getLogger()->debug("Check for lag! Owned plot popup sent to ".$event->getPlayer()->getName());
            } else {
                $ownerPopup = $this->plugin->getLanguage()->translateString("popup.available");
                $paddingSize = floor((strlen($popup) - strlen($ownerPopup)) / 2);
                $paddingPopup = str_repeat(" ", max(0, -$paddingSize));
                $paddingOwnerPopup = str_repeat(" ", max(0, $paddingSize));
	            $popup = TextFormat::WHITE . $paddingPopup . $popup . "\n" .
		            TextFormat::WHITE . $paddingOwnerPopup . $ownerPopup;
	            $this->plugin->getLogger()->debug("Check for lag! Availability plot popup sent to ".$event->getPlayer()->getName());
            }
            $event->getPlayer()->sendTip($popup);
            #$event->getPlayer()->sendPopup($popup);
        }
    }
}