<?php
namespace MyPlot\provider;

use onebone\economyapi\EconomyAPI;
use pocketmine\Player;

class EconomySProvider implements EconomyProvider
{
    public function addMoney(Player $player, $amount) {
        if (EconomyAPI::getInstance()->addMoney($player, $amount, true) === EconomyAPI::RET_SUCCESS) {
            return true;
        } else {
            return false;
        }
    }

    public function reduceMoney(Player $player, $amount) {
        if (EconomyAPI::getInstance()->reduceMoney($player, $amount, true) === EconomyAPI::RET_SUCCESS) {
            return true;
        } else {
            return false;
        }
    }
}