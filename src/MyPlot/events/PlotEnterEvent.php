<?php

namespace MyPlot\events;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;

class PlotEnterEvent extends PluginEvent implements Cancellable{

	private $plot, $player;

	/**
	 * PlotEnterEvent constructor.
	 * @param MyPlot $plugin
	 * @param Player $player
	 * @param Plot $plot
	 */
	public function __construct(MyPlot $plugin, Player $player, Plot $plot){
		parent::__construct($plugin);
		$this->setPlayer($player);
		$this->setPlot($plot);
	}

	/**
	 * @return mixed
	 */
	public function getPlayer(){
		return $this->player;
	}

	/**
	 * @param mixed $player
	 */
	public function setPlayer($player){
		$this->player = $player;
	}

	/**
	 * @return mixed
	 */
	public function getPlot(){
		return $this->plot;
	}

	/**
	 * @param mixed $plot
	 */
	public function setPlot($plot){
		$this->plot = $plot;
	}
}