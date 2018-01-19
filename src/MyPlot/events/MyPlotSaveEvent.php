<?php
namespace MyPlot\events;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\event\Cancellable;

class MyPlotSaveEvent extends MyPlotPlotEvent implements Cancellable {
	public static $handlerList = null;
	const SQLITE3 = 0;
	const MySQL = 1;
	const JSON = 2;
	const YAML = 3;
	const OTHER = -1;
	/** @var int $type */
	private $type;
	public function __construct(MyPlot $plugin, string $issuer, int $type, Plot $plot) {
		$this->type = $type;
		parent::__construct($plugin, $issuer, $plot);
	}

	/**
	 * @return int
	 */
	public function getType() : int {
		return $this->type;
	}
}