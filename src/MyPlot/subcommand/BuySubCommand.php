<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

class BuySubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.buy");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool{
		Await::f2c(
			function() use ($sender, $args) : \Generator{
				if($this->internalAPI->getEconomyProvider() === null){
					/** @noinspection PhpParamsInspection */
					$command = new ClaimSubCommand($this->plugin, "claim");
					$command->execute($sender, []);
					return;
				}
				$plot = yield $this->internalAPI->generatePlotByPosition($sender->getPosition());
				if($plot === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
					return;
				}
				if($plot->owner === $sender->getName() and !$sender->hasPermission("myplot.admin.buy")){
					$sender->sendMessage(TextFormat::RED . $this->translateString("buy.noself"));
					return;
				}
				if($plot->price <= 0){
					$sender->sendMessage(TextFormat::RED . $this->translateString("buy.notforsale"));
					return;
				}
				$maxPlots = $this->plugin->getMaxPlotsOfPlayer($sender);
				if(count(yield $this->internalAPI->generatePlotsOfPlayer($sender->getName(), null)) >= $maxPlots){
					$sender->sendMessage(TextFormat::RED . $this->translateString("claim.maxplots", [$maxPlots]));
					return;
				}
				$price = $plot->price;
				if(strtolower($args[0] ?? "") !== $this->translateString("confirm")){
					$sender->sendMessage($this->translateString("buy.confirm", ["$plot->X;$plot->Z", $price]));
					return;
				}
				$oldOwner = $this->plugin->getServer()->getPlayerExact($plot->owner);
				if(yield $this->internalAPI->generateBuyPlot($plot, $sender)){
					$sender->sendMessage($this->translateString("buy.success", ["$plot->X;$plot->Z", $price]));
					$oldOwner?->sendMessage($this->translateString("buy.sold", [$sender->getName(), "$plot->X;$plot->Z", $price])); // TODO: queue messages for sending when player rejoins
				}else{
					$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
				}
			}
		);
		return true;
	}
}