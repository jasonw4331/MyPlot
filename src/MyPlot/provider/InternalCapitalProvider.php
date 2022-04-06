<?php
declare(strict_types=1);
namespace MyPlot\provider;

use pocketmine\player\Player;
use SOFe\Capital\Capital;
use SOFe\Capital\LabelSet;
use SOFe\Capital\Plugin\MainClass;
use SOFe\Capital\Schema;

final class InternalCapitalProvider implements InternalEconomyProvider{

	private Schema\Complete $selector;

	public function __construct(){
		Capital::api('0.1.0', function(Capital $api){
			$this->selector = $api->completeConfig(null); // use null to get the default schema from Capital
		});
	}

	/**
	 * @inheritDoc
	 */
	public function reduceMoney(Player $player, int $amount, string $reason = 'Unknown') : \Generator{
		/** @var Capital $api */
		$api = yield from Capital::get(MainClass::$context);
		yield from $api->takeMoney(
			oracleName: "MyPlot",
			player: $player,
			schema: $this->selector,
			amount: $amount,
			transactionLabels: new LabelSet(["reason" => $reason]),
		);
	}

	/**
	 * @inheritDoc
	 */
	public function addMoney(Player $player, int $amount, string $reason = 'Unknown') : \Generator{
		/** @var Capital $api */
		$api = yield from Capital::get(MainClass::$context);
		yield from $api->addMoney(
			oracleName: "MyPlot",
			player: $player,
			schema: $this->selector,
			amount: $amount,
			transactionLabels: new LabelSet(["reason" => $reason]),
		);
	}

	/**
	 * @inheritDoc
	 */
	public function transactMoney(Player $player1, Player $player2, int $amount, string $reason = 'Unknown') : \Generator{
		/** @var Capital $api */
		$api = yield from Capital::get(MainClass::$context);
		yield from $api->pay(
			src: $player1,
			dest: $player2,
			schema: $this->selector,
			amount: $amount,
			transactionLabels: new LabelSet(["reason" => $reason]),
		);
	}
}