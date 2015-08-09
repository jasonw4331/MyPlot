<?php
namespace MyPlot;

use pocketmine\block\Lava;
use pocketmine\block\Water;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\Listener;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\level\LevelUnloadEvent;
use pocketmine\utils\Config;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;

class EventListener implements Listener
{
    /** @var MyPlot */
    private $plugin;

    public function __construct(MyPlot $plugin){
        $this->plugin = $plugin;
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
            $levelName = $event->getLevel()->getName();
            $filePath = $this->plugin->getDataFolder() . "worlds/" . $levelName . ".yml";
            $config = $this->plugin->getConfig();
            $default = [
                "MaxPlotsPerPlayer" => $config->getNested("DefaultWorld.MaxPlotsPerPlayer"),
                "ClaimPrice" => $config->getNested("DefaultWorld.ClaimPrice"),
                "ClearPrice" => $config->getNested("DefaultWorld.ClearPrice"),
                "DisposePrice" => $config->getNested("DefaultWorld.DisposePrice"),
                "ResetPrice" => $config->getNested("DefaultWorld.ResetPrice"),
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
        $this->plugin->unloadLevelSettings($levelName);
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
         */

        $levelName = $event->getBlock()->getLevel()->getName();
        if ($this->plugin->isLevelLoaded($levelName)) {
            $event->setCancelled(true);
        }
        if ($event->getBlock() instanceof Water or $event->getBlock() instanceof Lava) {
            $event->setCancelled(true);
        }
    }

    public function onExplosion(EntityExplodeEvent $event) {
        $levelName = $event->getEntity()->getLevel()->getName();
        if ($this->plugin->isLevelLoaded($levelName)) {
            $event->setCancelled(true);
        }

        /* Allow explosions but only break blocks inside the plot the tnt is in.
         * Disabled due to tnt cannons not being stopped

        $levelName = $event->getEntity()->getLevel()->getName();
        if (!$this->plugin->isLevelLoaded($levelName)) {
            return;
        }
        $plot = $this->plugin->getPlotByPosition($event->getPosition());
        if ($plot === null) {
            $event->setCancelled(true);
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
        */
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
            if ($plot->owner == $username or $plot->isHelper($username) or $event->getPlayer()->hasPermission("myplot.admin.build.plot")) {
                return;
            }
        } elseif ($event->getPlayer()->hasPermission("myplot.admin.build.road")) {
            return;
        }
        $event->setCancelled(true);
    }
}