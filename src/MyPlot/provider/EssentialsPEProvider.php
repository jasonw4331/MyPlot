<?php
declare(strict_types=1);
namespace MyPlot\provider;

use EssentialsPE\Loader;
use pocketmine\Player;

class EssentialsPEProvider implements EconomyProvider {
	/** @var Loader $plugin */
	private $plugin;

	/**
	 * EssentialsPEProvider constructor.
	 *
	 * @param Loader $plugin
	 */
	public function __construct(Loader $plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * @param Player $player
	 * @param float $amount
	 *
	 * @return bool
	 */
	public function reduceMoney(Player $player, float $amount) : bool {
		if($amount === 0) {
			return true;
		}elseif($amount < 0) {
			$amount = -$amount;
		}
		$pre = $this->plugin->getAPI()->getPlayerBalance($player);
		$this->plugin->getAPI()->addToPlayerBalance($player, (int) -$amount);
		if($this->plugin->getAPI()->getPlayerBalance($player) == $pre - (int) $amount) {
			$this->plugin->getLogger()->debug("MyPlot reduced money of ".$player->getName());
			return true;
		}
		$this->plugin->getLogger()->debug("MyPlot failed to reduce money of ".$player->getName());
		return false;
	}
}