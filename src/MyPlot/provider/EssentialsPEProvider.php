<?php
namespace MyPlot\provider;

use EssentialsPE\Loader;
use pocketmine\Player;

class EssentialsPEProvider implements EconomyProvider
{
	/** @var Loader */
	private $plugin;

	public function __construct(Loader $plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * @param Player $player
	 * @param float $amount
	 * @return bool
	 */
	public function reduceMoney(Player $player, float $amount) : bool {
		if ($amount == 0) {
			return true;
		} elseif ($amount < 0) {
			$pre = $this->plugin->getAPI()->getPlayerBalance($player);
			$this->plugin->getAPI()->addToPlayerBalance($player, $amount);
		} else {
			$pre = $this->plugin->getAPI()->getPlayerBalance($player);
			$this->plugin->getAPI()->addToPlayerBalance($player, -$amount);
		}
		if($this->plugin->getAPI()->getPlayerBalance($player) == $pre - $amount) {
			$this->plugin->getLogger()->debug("MyPlot reduced money of ".$player->getName());
			return true;
		}
		$this->plugin->getLogger()->debug("MyPlot failed to reduce money of ".$player->getName());
		return false;
	}
}