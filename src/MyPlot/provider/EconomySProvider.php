<?php
declare(strict_types=1);
namespace MyPlot\provider;

use onebone\economyapi\EconomyAPI;
use pocketmine\player\IPlayer;
use pocketmine\player\Player;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;

class EconomySProvider implements EconomyProvider {
	public function __construct(private EconomyAPI $plugin) {}

	/**
	 * @inheritDoc
	 */
	public function reduceMoney(Player $player, int $amount, string $reason = "Unknown") : \Generator {
		0 && yield;
		if($amount === 0) {
			return true;
		}elseif($amount < 0) {
			$amount = -$amount;
		}
		$ret = $this->plugin->reduceMoney($player, $amount, false, "MyPlot");
		if($ret === EconomyAPI::RET_SUCCESS) {
			$this->plugin->getLogger()->debug("MyPlot Reduced money of " . $player->getName());
			return true;
		}
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function addMoney(Player $player, int $amount, string $reason = "Unknown") : \Generator {
		0 && yield;
		if($amount === 0) {
			return true;
		}
		$ret = $this->plugin->addMoney($player, $amount, false, "MyPlot");
		if($ret === EconomyAPI::RET_SUCCESS) {
			return true;
		}
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function transactMoney(Player $player1, Player $player2, int $amount, string $reason = "Unknown") : \Generator {
		0 && yield;
		if($amount < 1){
			return true;
		}
		$ret = $this->plugin->reduceMoney($player1, $amount, true, "MyPlot");
		if($ret !== EconomyAPI::RET_SUCCESS) {
			return false;
		}
		$ret = $this->plugin->addMoney($player2, $amount, true, "MyPlot");
		if($ret !== EconomyAPI::RET_SUCCESS) {
			return false;
		}
		return true;
	}
}