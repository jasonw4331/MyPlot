<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\plot\BasePlot;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class MyPlotCloneEvent extends MyPlotPlotEvent implements Cancellable{
	use CancellableTrait;

	protected BasePlot $clonePlot;

	public function __construct(BasePlot $originPlot, BasePlot $clonePlot){
		$this->clonePlot = $clonePlot;
		parent::__construct($originPlot);
	}

	public function setClonePlot(BasePlot $clonePlot) : void{
		$this->clonePlot = $clonePlot;
	}

	public function getClonePlot() : BasePlot{
		return $this->clonePlot;
	}
}