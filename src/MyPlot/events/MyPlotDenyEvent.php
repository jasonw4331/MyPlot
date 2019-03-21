<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\event\Cancellable;
use pocketmine\IPlayer;

class MyPlotDenyEvent extends MyPlotPlotEvent implements Cancellable {
	const ADD = 0;
	const REMOVE = 1;
	public static $handlerList = null;
	/** @var int $type */
	private $type;
	/** @var string $player */
	private $player;

	/**
	 * MyPlotDenyEvent constructor.
	 *
	 * @param Plot $plot
	 * @param int $type
	 * @param string $player
	 */
	public function __construct(Plot $plot, int $type, string $player) {
		$this->type = $type;
		$this->player = $player;
		parent::__construct($plot);
	}

	/**
	 * @return int
	 */
	public function getType() : int {
		return $this->type;
	}

	/**
	 * @param int $type
	 */
	public function setType(int $type) : void {
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getDenied() : string {
		return $this->player;
	}

	/**
	 * @param IPlayer|string $player
	 */
	public function setDenied($player) : void {
		if($player instanceof IPlayer) {
			$this->player = $player->getName();
		}elseif(is_string($player)) {
			$this->player = $player;
		}
	}
}