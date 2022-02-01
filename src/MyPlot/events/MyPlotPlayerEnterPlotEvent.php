<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

class MyPlotPlayerEnterPlotEvent extends MyPlotPlotEvent implements Cancellable {
	use CancellableTrait;

	private Player $player;

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

	public function getPlayer() : Player {
		return $this->player;
	}

	public function setPlayer(Player $player) : self {
		$this->player = $player;
		return $this;
	}
}