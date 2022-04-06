<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\plot\BasePlot;
use MyPlot\plot\SinglePlot;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class MyPlotResetEvent extends MyPlotPlotEvent implements Cancellable{
	use CancellableTrait;

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