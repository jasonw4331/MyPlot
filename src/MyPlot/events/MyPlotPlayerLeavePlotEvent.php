<?php
namespace MyPlot\events;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\event\Cancellable;
use pocketmine\Player;

class MyPlotPlayerLeavePlotEvent extends MyPlotPlotEvent implements Cancellable {
	public static $handlerList = null;
	/** @var Player $player */
	private $player;

	/**
	 * MyPlotPlayerLeavePlotEvent constructor.
	 *
	 * @param MyPlot $plugin
	 * @param string $issuer
	 * @param Plot $plot
	 * @param Player $player
	 */
	public function __construct(MyPlot $plugin, string $issuer, Plot $plot, Player $player){
		parent::__construct($plugin, $issuer, $plot);
		$this->player = $player;
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