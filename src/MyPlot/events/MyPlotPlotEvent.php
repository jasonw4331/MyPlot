<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\event\Event;

class MyPlotPlotEvent extends Event {
	/** @var Plot $plot */
	protected $plot;

	public function __construct(Plot $plot) {
		$this->plot = $plot;
	}

	/**
	 * @return Plot
	 */
	public function getPlot() : Plot {
		return $this->plot;
	}

	/**
	 * @param Plot $plot
	 *
	 * @return self
	 */
	public function setPlot(Plot $plot) : self {
		$this->plot = $plot;
		return $this;
	}
}