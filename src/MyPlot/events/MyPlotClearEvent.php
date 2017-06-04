<?php
namespace MyPlot\events;

use MyPlot\MyPlot;
use MyPlot\Plot;

class MyPlotClearEvent extends MyPlotPlotEvent {
	/** @var bool $reset */
	public $reset;
	public function __construct(MyPlot $plugin, $issuer, Plot $plot, $reset = false) {
		$this->reset = $reset;
		parent::__construct($plugin, $issuer, $plot);
	}
}