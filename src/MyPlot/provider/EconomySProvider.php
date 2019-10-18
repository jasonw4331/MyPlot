<?php
declare(strict_types=1);
namespace MyPlot\provider;

use onebone\economyapi\EconomyAPI;
use pocketmine\player\IPlayer;
use pocketmine\player\Player;

class EconomySProvider implements EconomyProvider {
	/** @var EconomyAPI $plugin */
	private $plugin;

	/**
	 * EconomySProvider constructor.
	 *
	 * @param EconomyAPI $plugin
	 */
	public function __construct(EconomyAPI $plugin) {
		$this->plugin = $plugin;
	}

	public function reduceMoney(Player $player, float $amount) : bool {
		if($amount == 0) {
			return true;
		}elseif($amount < 0) {
			$amount = -$amount;
		}
		$ret = $this->plugin->reduceMoney($player, $amount, true, "MyPlot");
		if($ret === EconomyAPI::RET_SUCCESS) {
			$this->plugin->getLogger()->debug("MyPlot Reduced money of " . $player->getName());
			return true;
		}
		$this->plugin->getLogger()->debug("MyPlot failed to reduce money of ".$player->getName());
		return false;
	}

	public function addMoney(IPlayer $player, float $amount) : bool {
		if($amount < 1)
			return true;
		$ret = $this->plugin->addMoney($player->getName(), $amount, true, "MyPlot");
		if($ret === EconomyAPI::RET_SUCCESS) {
			$this->plugin->getLogger()->debug("MyPlot Add money of " . $player->getName());
			return true;
		}
		$this->plugin->getLogger()->debug("MyPlot failed to add money of ".$player->getName());
		return false;
	}
}