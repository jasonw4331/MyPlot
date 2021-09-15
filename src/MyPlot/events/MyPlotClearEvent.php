<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\event\Cancellable;

class MyPlotClearEvent extends MyPlotPlotEvent implements Cancellable {
	private int $maxBlocksPerTick;

	/**
	 * MyPlotClearEvent constructor.
	 *
	 * @param Plot $plot
	 * @param int $maxBlocksPerTick
	 */
	public function __construct(Plot $plot, int $maxBlocksPerTick = 256) {
		$this->maxBlocksPerTick = $maxBlocksPerTick;
		parent::__construct($plot);
	}

	public function getMaxBlocksPerTick() : int {
		return $this->maxBlocksPerTick;
	}

	public function setMaxBlocksPerTick(int $maxBlocksPerTick) : self {
		$this->maxBlocksPerTick = $maxBlocksPerTick;
		return $this;
	}
}