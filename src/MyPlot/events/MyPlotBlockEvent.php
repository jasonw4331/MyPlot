<?php
declare(strict_types=1);
namespace MyPlot\events;

use MyPlot\Plot;
use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\player\Player;

class MyPlotBlockEvent extends MyPlotPlotEvent implements Cancellable {
	use CancellableTrait;

	/** @var Block $block */
	private $block;
	/** @var BlockPlaceEvent|BlockBreakEvent|PlayerInteractEvent|SignChangeEvent $event */
	private $event;
	/** @var Player $player */
	private $player;

	/**
	 * MyPlotBlockEvent constructor.
	 *
	 * @param Plot $plot
	 * @param Block $block
	 * @param Player $player
	 * @param BlockPlaceEvent|BlockBreakEvent|PlayerInteractEvent|SignChangeEvent $event
	 */
	public function __construct(Plot $plot, Block $block, Player $player, Event $event) {
		$this->block = $block;
		$this->player = $player;
		$this->event = $event;
		parent::__construct($plot);
	}

	public function getBlock() : Block {
		return $this->block;
	}

	/**
	 * @return BlockPlaceEvent|BlockBreakEvent|PlayerInteractEvent|SignChangeEvent
	 */
	public function getEvent() : Event {
		return $this->event;
	}

	public function getPlayer() : Player {
		return $this->player;
	}
}