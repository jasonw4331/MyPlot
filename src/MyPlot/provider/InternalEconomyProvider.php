<?php
declare(strict_types=1);
namespace MyPlot\provider;

use pocketmine\player\Player;

interface InternalEconomyProvider{
	/**
	 * @param Player $player
	 * @param int    $amount
	 * @param string $reason
	 *
	 * @return \Generator<bool>
	 */
	public function reduceMoney(Player $player, int $amount, string $reason = "Unknown") : \Generator;

	/**
	 * @param Player $player
	 * @param int    $amount
	 * @param string $reason
	 *
	 * @return \Generator<bool>
	 */
	public function addMoney(Player $player, int $amount, string $reason = "Unknown") : \Generator;

	/**
	 * @param Player $player1
	 * @param Player $player2
	 * @param int    $amount
	 * @param string $reason
	 *
	 * @return \Generator<bool>
	 */
	public function transactMoney(Player $player1, Player $player2, int $amount, string $reason = "Unknown") : \Generator;
}