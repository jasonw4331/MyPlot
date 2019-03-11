<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\event\Cancellable;

class MyPlotSettingEvent extends MyPlotPlotEvent implements Cancellable {
	/** @var Plot $newPlot */
	private $oldPlot;

	public function __construct(Plot $oldPlot, Plot $newPlot) {
		$this->oldPlot = $oldPlot;
		parent::__construct($newPlot);
	}

	/**
	 * @return Plot
	 */
	public function getOldPlot() : Plot {
		return $this->oldPlot;
	}

	/**
	 * @param Plot $oldPlot
	 */
	public function setOldPlot(Plot $oldPlot) : void {
		$this->oldPlot = $oldPlot;
	}
}