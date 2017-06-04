<?php
namespace MyPlot\provider;

use EconomyPlus\EconomyPlus;
use pocketmine\Player;

class EconomyPlusProvider implements EconomyProvider
{
	/** @var EconomyPlus  */
	public $plugin;

	public function __construct(EconomyPlus $plugin)
	{
		$this->plugin = $plugin;
	}

	/**
	 * @param Player $player
	 * @param float $amount
	 * @return bool
	 */
	public function reduceMoney(Player $player, float $amount) : bool {
		return EconomyPlus::getInstance()->reduceMoney($player, $amount);
	}
}