<?php
namespace MyPlot\provider;

use pocketmine\Player;
use PocketMoney\PocketMoney;

class PocketMoneyProvider implements EconomyProvider
{
    private $plugin;

    public function __construct(PocketMoney $plugin) {
        $this->plugin = $plugin;
    }

    public function reduceMoney(Player $player, $amount) {
        $money = $this->plugin->getMoney($player->getName());
        if ($money === false or ($money - $amount) < 0) {
            return false;
        }
        return $this->plugin->setMoney($player->getName(), $money - $amount);
    }
}