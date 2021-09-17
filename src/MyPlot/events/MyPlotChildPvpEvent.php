<?php

namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\Player;

# same as MyPlotPvpEvent, but it's for Arrow and so
class MyPlotChildPvpEvent extends MyPlotPlotEvent implements Cancellable
{

    private Entity $attacker;
	private Player $damaged;
	private ?EntityDamageByChildEntityEvent $event;

	public function __construct(Plot $plot, Entity $attacker, Player $damaged, ?EntityDamageByChildEntityEvent $event = null) {
		$this->attacker = $attacker;
		$this->damaged = $damaged;
		$this->event = $event;
		parent::__construct($plot);
	}

	public function getAttacker() : Entity {
		return $this->attacker;
	}

	public function getDamaged() : Player {
		return $this->damaged;
	}

	public function getEvent() : ?EntityDamageByChildEntityEvent {
		return $this->event;
	}
}