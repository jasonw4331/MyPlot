<?php
namespace MyPlot\provider;

use pocketmine\Player;

interface EconomyProvider{
	public function reduceMoney(Player $player, $amount) : bool;
}