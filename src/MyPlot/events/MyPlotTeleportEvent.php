<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\Player;

class MyPlotTeleportEvent extends MyPlotPlayerEnterPlotEvent {
	/** @var bool $center */
	private $center = false;

	public function __construct(Plot $plot, Player $player, bool $center = false) {
		$this->center = $center;
		parent::__construct($plot, $player);
	}

	/**
	 * @return bool
	 */
	public function toCenter() : bool {
		return $this->center;
	}

	/**
	 * @param bool $center
	 *
	 * @return self
	 */
	public function setToCenter(bool $center) : self {
		$this->center = $center;
		return $this;
	}
}