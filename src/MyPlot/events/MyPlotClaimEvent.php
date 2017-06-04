<?php
namespace MyPlot\events;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\Player;

class MyPlotClaimEvent extends MyPlotPlotEvent {
	/** @var string $claimer */
	private $claimer;
	public function __construct(MyPlot $plugin, string $issuer, Plot $plot, string $claimer) {
		$this->claimer = $claimer;
		parent::__construct($plugin, $issuer, $plot);
	}
	public function getClaimer() : string {
		return $this->claimer;
	}
	public function setClaimer($claimer) {
		if($claimer instanceof Player) {
			$this->claimer = $claimer->getName();
		}elseif(is_string($claimer)) {
			$this->claimer = $claimer;
		}
	}
}