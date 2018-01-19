<?php
namespace MyPlot\events;


use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\event\Cancellable;
use pocketmine\Player;

class MyPlotHelperEvent extends MyPlotPlotEvent implements Cancellable {
	public static $handlerList = null;
	const ADD = 0;
	const REMOVE = 1;
	/** @var Plot $plot */
	private $plot;
	/** @var int $type */
	private $type;
	/** @var string $player */
	private $player;

	/**
	 * MyPlotHelperEvent constructor.
	 *
	 * @param MyPlot $plugin
	 * @param string $issuer
	 * @param Plot $plot
	 * @param int $type
	 * @param string $player
	 */
	public function __construct(MyPlot $plugin, string $issuer, Plot $plot, int $type, string $player) {
		$this->plot = $plot;
		$this->type = $type;
		$this->player = $player;
		parent::__construct($plugin, $issuer, $plot);
	}

	/**
	 * @return int
	 */
	public function getType() : int {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getHelper() : string {
		return $this->player;
	}

	/**
	 * @param Player|string $player
	 */
	public function setHelper($player) {
		if($player instanceof Player) {
			$this->player = $player->getName();
		}elseif(is_string($player)) {
			$this->player = $player;
		}
	}
}