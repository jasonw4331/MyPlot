<?php
namespace MyPlot\events;

use MyPlot\MyPlot;
use MyPlot\Plot;

class MyPlotSaveEvent extends MyPlotEvent {
	const SQLITE3 = 0;
	const MySQL = 1;
	const JSON = 2;
	const YAML = 3;
	const OTHER = -1;
	/** @var int $type */
	private $type;
	/** @var Plot $plot */
	private $plot;
	public function __construct(MyPlot $plugin, string $issuer, int $type, Plot $plot) {
		$this->type = $type;
		$this->plot = $plot;
		parent::__construct($plugin, $issuer);
	}
	public function getType() : int {
		return $this->type;
	}
	public function getPlot() : plot {
		return $this->plot;
	}
	public function setPlot(Plot $plot) {
		$this->plot = $plot;
	}
}