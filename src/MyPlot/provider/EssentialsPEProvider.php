<?php
namespace MyPlot\provider;

use EssentialsPE\Loader;
use pocketmine\Player;

class EssentialsPEProvider implements EconomyProvider {
	/** @var Loader */
	private $plugin;

	public function __construct(Loader $plugin) {
		$this->plugin = $plugin;
	}

	public function reduceMoney(Player $player, $amount) {
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
			return true;
		}
		return false;
	}
}