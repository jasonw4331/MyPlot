<?php
declare(strict_types=1);
namespace MyPlot\provider;

use MyPlot\MyPlot;
use Paroxity\ParoxityEcon\ParoxityEconAPI;
use pocketmine\Player;

class ParoxityEconProvider implements EconomyProvider
{
	/** @var ParoxityEconAPI $plugin */
	private $plugin;

	public function __construct(ParoxityEconAPI $plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * @param Player $player
	 * @param float $amount
	 *
	 * @return bool
	 */
	public function reduceMoney(Player $player, float $amount) : bool {
		if($amount == 0) {
			return true;
		}elseif($amount < 0) {
			$amount = -$amount;
		}
		$this->plugin->deductMoney($player->getUniqueId()->toString(), $amount, true, function(bool $success) use ($player) : void {
			if($success)
				MyPlot::getInstance()->getLogger()->debug("MyPlot reduced money of ".$player->getName());
			else
				MyPlot::getInstance()->getLogger()->debug("MyPlot failed to reduce money of ".$player->getName());
		});
		return true; // TODO: async return
	}
}