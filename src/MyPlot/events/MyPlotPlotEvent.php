<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\plot\BasePlot;
use pocketmine\event\Event;

class MyPlotPlotEvent extends Event{
	protected BasePlot $plot;

	public function __construct(BasePlot $plot){
		$this->plot = $plot;
	}

	public function getPlot() : BasePlot{
		return $this->plot;
	}

	public function setPlot(BasePlot $plot) : self{
		$this->plot = $plot;
		return $this;
	}
}