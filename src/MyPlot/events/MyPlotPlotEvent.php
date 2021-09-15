<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\event\Event;

class MyPlotPlotEvent extends Event {
	protected Plot $plot;

	public function __construct(Plot $plot) {
		$this->plot = $plot;
	}

	public function getPlot() : Plot {
		return $this->plot;
	}

	public function setPlot(Plot $plot) : self {
		$this->plot = $plot;
		return $this;
	}
}