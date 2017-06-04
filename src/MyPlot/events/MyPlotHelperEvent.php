<?php
namespace MyPlot\events;


use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\Player;

class MyPlotHelperEvent extends MyPlotPlotEvent {
	const ADD = 0;
	const REMOVE = 1;
	/** @var Plot $plot */
	private $plot;
	/** @var int $type */
	private $type;
	/** @var string $player */
	private $player;
	public function __construct(MyPlot $plugin, string $issuer, Plot $plot, int $type, string $player) {
		$this->plot = $plot;
		$this->type = $type;
		$this->player = $player;
		parent::__construct($plugin, $issuer, $plot);
	}
	public function getType() : int {
		return $this->type;
	}
	public function getHelper() : string {
		return $this->player;
	}
	public function setHelper($player) {
		if($player instanceof Player) {
			$this->player = $player->getName();
		}elseif(is_string($player)) {
			$this->player = $player;
		}
	}
}