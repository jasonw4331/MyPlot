<?php
namespace MyPlot\task;

use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\level\particle\HappyVillagerParticle;
use pocketmine\math\Vector3;
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
        $this->plugin->getServer()->getLevelByName($lname)->addParticle(new HappyVillagerParticle($plocation));
        $text = [];
        
        $br = TF::RESET. "\n"; // line break
        
        $text[0] = TF::BLUE. TF::BOLD. $this->plot->name;
        $text[1] = TF::DARK_GREEN."By: ".$this->plot->owner;
        $text[2] = TF::GREEN."DONE";
       
        $level = $this->getOwner()->getServer()->getLevel($lname);
       
        $title = TF::RESET. $text[0]. TF::RESET;
        $texter = $text[1].$br.$text[2];
        $particle = new FloatingTextParticle(new Vector3($plocation->x, $plocation->y+5, $plocation->z), $texter, $title);
        $p = null;
        foreach ($this->getOwner()->getServer()->getOnlinePlayers() as $player) {
            $p = $player;
        }
        $level->addParticle($particle, $p);
    }
}
