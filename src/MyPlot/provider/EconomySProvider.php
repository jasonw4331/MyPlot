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

	public function reduceMoney(Player $player, $amount) : bool {
        if ($amount == 0) {
            return true;
        } elseif ($amount < 0) {
            $ret = $this->plugin->addMoney($player, $amount, true);
        } else {
            $ret = $this->plugin->reduceMoney($player, $amount, true);
        }
        if($ret == 1) {
	        $this->plugin->getLogger()->debug("MyPlot Reduced money of ".$player->getName());
	        return true;
        }
		$this->plugin->getLogger()->debug("MyPlot failed to reduce money of ".$player->getName());
        return false;
    }
}