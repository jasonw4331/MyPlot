<?php
namespace MyPlot\provider;

use onebone\economyapi\EconomyAPI;
use pocketmine\Player;

class EconomySProvider implements EconomyProvider
{
    public function reduceMoney(Player $player, $amount) {
        if ($amount == 0) {
            return true;
        } elseif ($amount < 0) {
            $ret = EconomyAPI::getInstance()->addMoney($player, -$amount, true);
        } else {
            $ret = EconomyAPI::getInstance()->reduceMoney($player, $amount, true);
        }

        return ($ret === EconomyAPI::RET_SUCCESS);
    }
}