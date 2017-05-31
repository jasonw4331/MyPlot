<?php
namespace MyPlot\events;

use MyPlot\MyPlot;

class MyPlotSaveEvent extends MyPlotEvent {
	const SQLITE3 = 0;
	const MySQL = 1;
	const JSON = 2;
	const YAML = 3;
	const OTHER = -1;
	/** @var int $type */
	private $type;
	public function __construct(MyPlot $plugin, $issuer, int $type) {
		$this->type = $type;
		parent::__construct($plugin, $issuer);
	}
	public function getType() {
		return $this->type;
	}
}