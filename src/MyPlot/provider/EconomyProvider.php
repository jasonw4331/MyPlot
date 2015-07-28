<?php
namespace MyPlot\provider;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;

interface EconomyProvider
{
    /**
     * @param Player $player
     * @param int $amount
     * @return bool
     */
    public function reduceMoney(Player $player, $amount);
}