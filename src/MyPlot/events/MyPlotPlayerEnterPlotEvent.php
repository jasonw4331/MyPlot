<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\event\Cancellable;
use pocketmine\Player;

class MyPlotPlayerEnterPlotEvent extends MyPlotPlotEvent implements Cancellable {
	/** @var Player $player */
	private $player;

	/**
	 * MyPlotPlayerEnterPlotEvent constructor.
	 *
	 * @param Plot $plot
	 * @param Player $player
	 */
	public function __construct(Plot $plot, Player $player) {
		$this->player = $player;
		parent::__construct($plot);
	}

	/**
	 * @return Player
	 */
	public function getPlayer() : Player {
		return $this->player;
	}

	/**
	 * @param Player $player
	 *
	 * @return self
	 */
	public function setPlayer(Player $player) : self {
		$this->player = $player;
		return $this;
	}
}