<?php
declare(strict_types=1);
namespace MyPlot\provider;

use pocketmine\IPlayer;
use pocketmine\Player;

interface EconomyProvider {
	public function reduceMoney(Player $player, float $amount) : bool;

	public function addMoney(IPlayer $player, float $amount) : bool;
}