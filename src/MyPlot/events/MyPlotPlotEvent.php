<?php
namespace MyPlot\events;

use MyPlot\MyPlot;
use MyPlot\Plot;

abstract class MyPlotPlotEvent extends MyPlotEvent {
	/** @var Plot $plot */
	protected $plot;

	/**
	 * MyPlotPlotEvent constructor.
	 *
	 * @param MyPlot $plugin
	 * @param string $issuer
	 * @param Plot $plot
	 */
	public function __construct(MyPlot $plugin, string $issuer, Plot $plot) {
		$this->plot = $plot;
		parent::__construct($plugin, $issuer);
	}

	/**
	 * @return Plot
	 */
	public function getPlot() : Plot {
		return $this->plot;
	}

	/**
	 * @param Plot $plot
	 */
	public function setPlot(Plot $plot) {
		$this->plot = $plot;
	}
}