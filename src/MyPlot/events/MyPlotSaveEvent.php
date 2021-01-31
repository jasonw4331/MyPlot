<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\event\Cancellable;

class MyPlotSaveEvent extends MyPlotPlotEvent implements Cancellable {
	public const SQLITE3 = 0;
	public const MySQL = 1;
	public const JSON = 2;
	public const YAML = 3;
	public const OTHER = -1;
	/** @var int $type */
	private $type;

	public function __construct(int $type, Plot $plot) {
		$this->type = $type;
		parent::__construct($plot);
	}

	public function getSaveType() : int {
		return $this->type;
	}
}