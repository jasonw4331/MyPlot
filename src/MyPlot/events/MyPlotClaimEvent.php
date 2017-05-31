<?php
namespace MyPlot\events;

use MyPlot\MyPlot;
use MyPlot\Plot;

class MyPlotClaimEvent extends MyPlotEvent {
	/** @var Plot $plot */
	private $plot;
	public function __construct(MyPlot $plugin, string $issuer, Plot $plot) {
		$this->plot = $plot;
		parent::__construct($plugin, $issuer);
	}
	public function getPlot() : Plot {
		return $this->plot;
	}
	public function setplot(Plot $plot) {
		$this->plot = $plot;
	}
}