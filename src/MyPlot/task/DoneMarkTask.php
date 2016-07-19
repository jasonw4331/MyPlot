<?php
namespace MyPlot\task;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\level\particle\HappyVillagerParticle;
use pocketmine\scheduler\PluginTask;
class DoneMarkTask extends PluginTask
{
    private $done, $plot, $plugin;
    public function __construct(MyPlot $plugin, Plot $plot) {
        parent::__construct($plugin);
        $this->plot = $plot;
        $this->plugin = $plugin;
        $this->done = $plot->done;
    }
    public function onRun($tick) {
        $lname = $this->plot->levelName;
        $plocation = $this->plugin->getPlotMid($this->plot);
        $this->plugin->getServer()->getLevel($lname)->addParticle(new HappyVillagerParticle($plocation));
    }
}