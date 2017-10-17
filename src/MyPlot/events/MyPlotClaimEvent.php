<?php
namespace MyPlot\events;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\event\Cancellable;
use pocketmine\Player;

class MyPlotClaimEvent extends MyPlotPlotEvent implements Cancellable {
	public static $handlerList = null;
	/** @var string $claimer */
	private $claimer;

	/**
	 * MyPlotClaimEvent constructor.
	 *
	 * @param MyPlot $plugin
	 * @param string $issuer
	 * @param Plot $plot
	 * @param string $claimer
	 */
	public function __construct(MyPlot $plugin, string $issuer, Plot $plot, string $claimer) {
		$this->claimer = $claimer;
		parent::__construct($plugin, $issuer, $plot);
	}

	/**
	 * @return string
	 */
	public function getClaimer() : string {
		return $this->claimer;
	}

	/**
	 * @param Player|string $claimer
	 */
	public function setClaimer($claimer) {
		if($claimer instanceof Player) {
			$this->claimer = $claimer->getName();
		}elseif(is_string($claimer)) {
			$this->claimer = $claimer;
		}
	}
}