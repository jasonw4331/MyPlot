<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class MyPlotSettingEvent extends MyPlotPlotEvent implements Cancellable {
	use CancellableTrait;

	/** @var Plot $oldPlot */
	private $oldPlot;

	public function __construct(Plot $oldPlot, Plot $newPlot) {
		$this->oldPlot = $oldPlot;
		parent::__construct($newPlot);
	}

	public function getOldPlot() : Plot {
		return $this->oldPlot;
	}

	public function setOldPlot(Plot $oldPlot) : self {
		$this->oldPlot = $oldPlot;
		return $this;
	}
}