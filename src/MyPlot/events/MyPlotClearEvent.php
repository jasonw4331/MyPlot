<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\event\Cancellable;

class MyPlotClearEvent extends MyPlotPlotEvent implements Cancellable {
	public static $handlerList = null;
	/** @var int $maxBlocksPerTick */
	private $maxBlocksPerTick = 256;

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

	/**
	 * @return int
	 */
	public function getMaxBlocksPerTick() : int {
		return $this->maxBlocksPerTick;
	}

	/**
	 * @param int $maxBlocksPerTick
	 */
	public function setMaxBlocksPerTick(int $maxBlocksPerTick) : void {
		$this->maxBlocksPerTick = $maxBlocksPerTick;
	}
}