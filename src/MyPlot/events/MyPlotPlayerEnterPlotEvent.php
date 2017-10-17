<?php
namespace MyPlot\events;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\Player;

class MyPlotPlayerEnterPlotEvent extends MyPlotPlotEvent {
	public static $handlerList = null;
	/** @var Player $player */
	private $player;

	/**
	 * MyPlotPlayerEnterPlotEvent constructor.
	 *
	 * @param MyPlot $plugin
	 * @param string $issuer
	 * @param Plot $plot
	 * @param Player $player
	 */
	public function __construct(MyPlot $plugin, string $issuer, Plot $plot, Player $player) {
		$this->player = $player;
		parent::__construct($plugin, $issuer, $plot);
	}

	/**
	 * @return Player
	 */
	public function getPlayer() : Player {
		return $this->player;
	}
}