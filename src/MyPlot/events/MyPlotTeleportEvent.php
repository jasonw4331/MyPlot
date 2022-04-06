<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\plot\BasePlot;
use pocketmine\player\Player;

class MyPlotTeleportEvent extends MyPlotPlayerEnterPlotEvent{
	private bool $center;

	public function __construct(BasePlot $plot, Player $player, bool $center = false){
		$this->center = $center;
		parent::__construct($plot, $player);
	}

	public function toCenter() : bool{
		return $this->center;
	}

	public function setToCenter(bool $center) : self{
		$this->center = $center;
		return $this;
	}
}