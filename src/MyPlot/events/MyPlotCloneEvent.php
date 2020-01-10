<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\event\Cancellable;

class MyPlotCloneEvent extends MyPlotPlotEvent implements Cancellable {
	/** @var Plot $clonePlot **/
	protected $clonePlot;

	/**
	 * MyPlotCloneEvent constructor.
	 *
	 * @param Plot $originPlot
	 * @param Plot $clonePlot
	 */
	public function __construct(Plot $originPlot, Plot $clonePlot) {
		$this->clonePlot = $clonePlot;
		parent::__construct($originPlot);
	}

	/**
	 * @param Plot $clonePlot
	 */
	public function setClonePlot(Plot $clonePlot) : void {
		$this->clonePlot = $clonePlot;
	}

	/**
	 * @return Plot
	 */
	public function getClonePlot() : Plot {
		return $this->clonePlot;
	}
}