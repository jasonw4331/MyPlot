<?php
namespace MyPlot\events;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\event\Cancellable;

class MyPlotClearEvent extends MyPlotPlotEvent implements Cancellable {
	public static $handlerList = null;
	/** @var bool $reset */
	private $reset;

	/**
	 * MyPlotClearEvent constructor.
	 *
	 * @param MyPlot $plugin
	 * @param string $issuer
	 * @param Plot $plot
	 * @param bool $reset
	 */
	public function __construct(MyPlot $plugin, string $issuer, Plot $plot, bool $reset = false) {
		$this->reset = $reset;
		parent::__construct($plugin, $issuer, $plot);
	}

	/**
	 * @return bool
	 */
	public function isReset() : bool {
		return $this->reset;
	}
}