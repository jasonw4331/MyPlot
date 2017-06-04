<?php
namespace MyPlot\events;

use MyPlot\MyPlot;
use MyPlot\Plot;

class MyPlotPlotEvent extends MyPlotEvent {
	/** @var Plot $plot */
	protected $plot;
	public function __construct(MyPlot $plugin, $issuer, Plot $plot) {
		$this->plot = $plot;
		parent::__construct($plugin, $issuer);
	}
	public function getPlot() : Plot {
		return $this->plot;
	}
	public function setPlot(Plot $plot) {
		$this->plot = $plot;
	}
}