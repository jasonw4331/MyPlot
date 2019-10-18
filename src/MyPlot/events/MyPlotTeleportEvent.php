<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\player\Player;

class MyPlotTeleportEvent extends MyPlotPlayerEnterPlotEvent {
	/** @var bool $center */
	private $center = false;

	public function __construct(Plot $plot, Player $player, bool $center = false) {
		$this->center = $center;
		parent::__construct($plot, $player);
	}

	public function toCenter() : bool {
		return $this->center;
	}

	public function setToCenter(bool $center) : self {
		$this->center = $center;
		return $this;
	}
}