<?php
declare(strict_types=1);

namespace MyPlot\provider;

use pocketmine\player\Player;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use SOFe\AwaitGenerator\Await;

final class EconomyWrapper{

	public function __construct(private InternalEconomyProvider $provider){ }

	/**
	 * @inheritDoc
	 */
	public function reduceMoney(Player $player, int $amount, string $reason = "Unknown") : Promise{
		$resolver = new PromiseResolver();
		Await::g2c(
			$this->provider->reduceMoney($player, $amount, $reason),
			fn(bool $success) => $resolver->resolve($success),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * @inheritDoc
	 */
	public function addMoney(Player $player, int $amount, string $reason = "Unknown") : Promise{
		$resolver = new PromiseResolver();
		Await::g2c(
			$this->provider->addMoney($player, $amount, $reason),
			fn(bool $success) => $resolver->resolve($success),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}

	/**
	 * @inheritDoc
	 */
	public function transactMoney(Player $player1, Player $player2, int $amount, string $reason = "Unknown") : Promise{
		$resolver = new PromiseResolver();
		Await::g2c(
			$this->provider->transactMoney($player1, $player2, $amount, $reason),
			fn(bool $success) => $resolver->resolve($success),
			fn(\Throwable $e) => $resolver->reject()
		);
		return $resolver->getPromise();
	}
}