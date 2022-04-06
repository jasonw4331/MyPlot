<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\plot\BasePlot;
use MyPlot\plot\SinglePlot;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class MyPlotSettingEvent extends MyPlotPlotEvent implements Cancellable{
	use CancellableTrait;

	private SinglePlot $oldPlot;

	public function __construct(SinglePlot $oldPlot, SinglePlot $newPlot){
		$this->oldPlot = $oldPlot;
		parent::__construct($newPlot);
	}

	public function getOldPlot() : SinglePlot{
		return $this->oldPlot;
	}

	/**
	 * @return SinglePlot
	 */
	public function getPlot() : BasePlot{
		return parent::getPlot();
	}
}