<?php
namespace MyPlot\task;

use pocketmine\level\particle\HappyVillagerParticle;
use pocketmine\scheduler\PluginTask;
use pocketmine\utils\TextFormat as TF;

use MyPlot\MyPlot;
use MyPlot\Plot;

class DoneMarkTask extends PluginTask {
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
        $text = [];
        
        $br = TF::RESET. "\n";
        
        $text[0] = TF::BLUE. F::BOLD. $this->plot->name;
        $text[1] = TF::DARK_GREEN."By: ".$this->plot->owner;
       
        $level = $this->getServer()->getLevel($lname);
       
        $title = TF::RESET. $text[0]. TF::RESET;
        $texter = $text[1];
        $particle = new FloatingTextParticle(new Vector3($plocation->x, $plocation->y+5, $plocation->z), $texter, $title);

        $level->addParticle($particle, $p);
    }
}
