<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\plot\BasePlot;
use MyPlot\plot\SinglePlot;

class MyPlotSaveEvent extends MyPlotPlotEvent{
	public function __construct(SinglePlot $plot){
		parent::__construct($plot);
	}

	/**
	 * @return SinglePlot
	 */
	public function getPlot() : BasePlot{
		return parent::getPlot();
	}
}