<?php
declare(strict_types=1);
namespace MyPlot\provider;

use onebone\economyapi\EconomyAPI;
use pocketmine\Player;

class EconomySProvider implements EconomyProvider
{
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
		$ret = $this->plugin->reduceMoney($player, $amount, true, "MyPlot");
		if($ret === EconomyAPI::RET_SUCCESS) {
			$this->plugin->getLogger()->debug("MyPlot Reduced money of " . $player->getName());
			return true;
		}
		$this->plugin->getLogger()->debug("MyPlot failed to reduce money of ".$player->getName());
		return false;
	}
}