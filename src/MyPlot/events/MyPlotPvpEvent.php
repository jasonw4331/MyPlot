<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\event\Cancellable;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;

class MyPlotPvpEvent extends MyPlotPlotEvent implements Cancellable {
	private Player $attacker;
	private Player $damaged;
	private ?EntityDamageByEntityEvent $event;

	public function __construct(Plot $plot, Player $attacker, Player $damaged, ?EntityDamageByEntityEvent $event = null) {
		$this->attacker = $attacker;
		$this->damaged = $damaged;
		$this->event = $event;
		parent::__construct($plot);
	}

	public function getAttacker() : Player {
		return $this->attacker;
	}

	public function getDamaged() : Player {
		return $this->damaged;
	}

	public function getEvent() : ?EntityDamageByEntityEvent {
		return $this->event;
	}
}