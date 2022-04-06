<?php
declare(strict_types=1);

namespace MyPlot\provider;

use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\libSQL\context\ClosureContext;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

class InternalBedrockEconomyProvider implements InternalEconomyProvider{

	public function __construct(private BedrockEconomyAPI $api){ }

	/**
	 * @inheritDoc
	 */
	public function reduceMoney(Player $player, int $amount, string $reason = "Unknown") : \Generator{
		$onSuccess = yield Await::RESOLVE;
		$onError = yield Await::REJECT;
		$this->api->subtractFromPlayerBalance(
			$player->getName(),
			$amount,
			ClosureContext::create(
				static function(bool $response, callable $stopRunning, ?string $error) use ($onSuccess, $onError) : void{
					$error === null ?: $onError($error);
					$onSuccess($response);
				}
			),
			'MyPlot'
		);
		return yield Await::ONCE;
	}

	/**
	 * @inheritDoc
	 */
	public function addMoney(Player $player, int $amount, string $reason = "Unknown") : \Generator{
		$onSuccess = yield Await::RESOLVE;
		$onError = yield Await::REJECT;
		$this->api->addToPlayerBalance(
			$player->getName(),
			$amount,
			ClosureContext::create(
				static function(bool $response, callable $stopRunning, ?string $error) use ($onSuccess, $onError) : void{
					$error === null ?: $onError($error);
					$onSuccess($response);
				}
			),
			'MyPlot'
		);
		return yield Await::ONCE;
	}

	/**
	 * @inheritDoc
	 */
	public function transactMoney(Player $player1, Player $player2, int $amount, string $reason = "Unknown") : \Generator{
		$onSuccess = yield Await::RESOLVE;
		$onError = yield Await::REJECT;
		$this->api->transferFromPlayerBalance(
			$player1->getName(),
			$player2->getName(),
			$amount,
			ClosureContext::create(
				static function(bool $response, callable $stopRunning, ?string $error) use ($onSuccess, $onError) : void{
					$error === null ?: $onError($error);
					$onSuccess($response);
				}
			)
		// no issuer for some reason
		);
		return yield Await::ONCE;
	}
}