<?php

namespace MyPlot\events;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\Player;

class MyPlotPlayerLeavePlotEvent extends MyPlotPlotEvent{
	public static $handlerList = null;
	/** @var Player */
	private $player;

	/**
	 * PlotEnterEvent constructor.
	 * @param MyPlot $plugin
	 * @param string $issuer
	 * @param Plot $plot
	 * @param Player $player
	 */
	public function __construct(MyPlot $plugin, string $issuer, Plot $plot, Player $player){
		parent::__construct($plugin, $issuer, $plot);
		$this->setPlayer($player);
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