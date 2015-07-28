<?php
namespace MyPlot\provider;

use pocketmine\Player;

interface EconomyProvider
{
    /**
     * @param Player $player
     * @param int $amount
     * @return bool
     */
    public function reduceMoney(Player $player, $amount);
}