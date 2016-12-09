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

	public function reduceMoney(Player $player, $amount) {
        if ($amount == 0) {
            return true;
        } elseif ($amount < 0) {
            $ret = $this->plugin->addMoney($player, $amount, true);
        } else {
            $ret = $this->plugin->reduceMoney($player, $amount, true);
        }
        return ($ret == 1);
    }
}