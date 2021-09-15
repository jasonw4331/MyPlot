<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\event\Cancellable;
use pocketmine\IPlayer;

class MyPlotDenyEvent extends MyPlotPlotEvent implements Cancellable {
	public const ADD = 0;
	public const REMOVE = 1;
	private int $type;
	private string $player;

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

	public function getType() : int {
		return $this->type;
	}

	public function setType(int $type) : self {
		$this->type = $type;
		return $this;
	}

	public function getDenied() : string {
		return $this->player;
	}

	/**
	 * @param IPlayer|string $player
	 *
	 * @return self
	 */
	public function setDenied($player) : self {
		if($player instanceof IPlayer) {
			$this->player = $player->getName();
		}elseif(is_string($player)) {
			$this->player = $player;
		}
		return $this;
	}
}