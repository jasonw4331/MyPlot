<?php
declare(strict_types=1);
namespace MyPlot\provider;

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
	 * @param Player|string $player
	 * @param float $amount
	 *
	 * @return bool
	 */
	public function addMoney($player, float $amount) : bool;
}