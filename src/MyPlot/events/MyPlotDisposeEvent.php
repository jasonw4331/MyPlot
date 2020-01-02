<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\event\Cancellable;

class MyPlotDisposeEvent extends MyPlotPlotEvent implements Cancellable {

	/**
	 * MyPlotClearEvent constructor.
	 *
	 * @param Plot $plot
	 */
	public function __construct(Plot $plot) {
		parent::__construct($plot);
	}
}