<?php
declare(strict_types=1);
namespace MyPlot\provider;

use pocketmine\IPlayer;
use pocketmine\Player;

interface EconomyProvider {
	/**
	 * @param Player $player
	 * @param float $amount
	 *
	 * @return bool
	 */
	public function reduceMoney(Player $player, float $amount) : bool;

	/**
	 * @param IPlayer $player
	 * @param float $amount
	 *
	 * @return bool
	 */
	public function addMoney(IPlayer $player, float $amount) : bool;
}