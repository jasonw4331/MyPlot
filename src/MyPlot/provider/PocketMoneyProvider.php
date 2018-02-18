<?php
declare(strict_types=1);
namespace MyPlot\provider;

use pocketmine\Player;
use PocketMoney\PocketMoney;

class PocketMoneyProvider implements EconomyProvider
{
	/** @var PocketMoney $plugin */
	private $plugin;

	/**
	 * PocketMoneyProvider constructor.
	 *
	 * @param PocketMoney $plugin
	 */
	public function __construct(PocketMoney $plugin) {
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
		$money = $this->plugin->getMoney($player->getName());
		if($money === false or ($money - $amount) < 0) {
			return false;
		}
		if($this->plugin->setMoney($player->getName(), $money - $amount)) {
			$this->plugin->getLogger()->debug("MyPlot reduced money of " . $player->getName());
			return true;
		}
		$this->plugin->getLogger()->debug("MyPlot failed to reduce money of " . $player->getName());
		return false;
	}
}