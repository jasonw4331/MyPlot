<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\event\Cancellable;

class MyPlotSaveEvent extends MyPlotPlotEvent implements Cancellable {
	const SQLITE3 = 0;
	const MySQL = 1;
	const JSON = 2;
	const YAML = 3;
	const OTHER = -1;
	/** @var int $type */
	private $type;

	public function __construct(int $type, Plot $plot) {
		$this->type = $type;
		parent::__construct($plot);
	}

	/**
	 * @return int
	 */
	public function getSaveType() : int {
		return $this->type;
	}
}