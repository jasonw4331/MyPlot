<?php
namespace MyPlot\provider;

use pocketmine\Player;
use PocketMoney\PocketMoney;

class PocketMoneyProvider implements EconomyProvider
{
	/** @var PocketMoney */
	private $plugin;

	public function __construct(PocketMoney $plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * @param Player $player
	 * @param float $amount
	 * @return bool
	 */
	public function reduceMoney(Player $player, float $amount) : bool {
		$money = $this->plugin->getMoney($player->getName());
		if($amount == 0) {
			return true;
		}
		if ($money === false or ($money - $amount) < 0) {
			return false;
		}
		if($this->plugin->setMoney($player->getName(), $money - $amount)) {
			$this->plugin->getLogger()->debug("MyPlot reduced money of ".$player->getName());
			return true;
		}
		$this->plugin->getLogger()->debug("MyPlot failed to reduce money of ".$player->getName());
		return false;
	}
}