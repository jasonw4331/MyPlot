<?php
namespace MyPlot\provider;

use ImagicalGamer\EconomyPlus\Main;

use pocketmine\Player;

class EconomyPlusProvider implements EconomyProvider
{
	/** @var Main $plugin */
	public $plugin;

	public function __construct(Main $plugin)
	{
		$this->plugin = $plugin;
	}

	/**
	 * @param Player $player
	 * @param float $amount
	 * @return bool
	 */
	public function reduceMoney(Player $player, float $amount) : bool {
		if($amount < 0) {
			$amount = -$amount;
		}
		if($amount === 0) {
			return false;
		}
		$this->plugin->subtractMoney($player, $amount);
		return true;
	}
}