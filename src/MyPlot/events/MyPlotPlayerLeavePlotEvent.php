<?php

namespace MyPlot\events;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;

class MyPlotPlayerLeavePlotEvent extends PluginEvent{
	public static $handlerList = null;
	/** @var Plot */
	private $plot;
	/** @var Player */
	private $player;

	/**
	 * PlotEnterEvent constructor.
	 * @param MyPlot $plugin
	 * @param Plot $plot
	 * @param Player $player
	 */
	public function __construct(MyPlot $plugin, Plot $plot, Player $player){
		parent::__construct($plugin);
		$this->setPlayer($player);
		$this->setPlot($plot);
	}

	/**
	 * @return Plot
	 */
	public function getPlot(): Plot{
		return $this->plot;
	}

	/**
	 * @param Plot $plot
	 */
	public function setPlot(Plot $plot){
		$this->plot = $plot;
	}

	/**
	 * @return Player
	 */
	public function getPlayer(): Player{
		return $this->player;
	}

	/**
	 * @param Player $player
	 */
	public function setPlayer(Player $player){
		$this->player = $player;
	}
}