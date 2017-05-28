<?php
namespace MyPlot\provider;

use onebone\economyapi\EconomyAPI;
use pocketmine\Player;

class EconomySProvider implements EconomyProvider
{
	/** @var EconomyAPI */
	private $plugin;

	public function __construct(EconomyAPI $plugin) {
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
			$ret = $this->plugin->addMoney($player, $amount, true, "MyPlot");
		} else {
			$ret = $this->plugin->reduceMoney($player, $amount, true, "MyPlot");
		}
		if($ret == 1) {
			$this->plugin->getLogger()->debug("MyPlot Reduced money of ".$player->getName());
			return true;
		}
		$this->plugin->getLogger()->debug("MyPlot failed to reduce money of ".$player->getName());
		return false;
	}
}