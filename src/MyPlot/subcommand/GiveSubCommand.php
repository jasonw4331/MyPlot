<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\GiveForm;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

class GiveSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.give");
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
				if(count($args) < 1){
					$sender->sendMessage($this->translateString("subcommand.usage", [$this->getUsage()]));
					return;
				}
				$newOwner = $args[0];
				$plot = yield $this->internalAPI->generatePlotByPosition($sender->getPosition());
				if($plot === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
					return;
				}
				if($plot->owner !== $sender->getName()){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
					return;
				}
				$newOwner = $this->plugin->getServer()->getPlayerByPrefix($newOwner);
				if(!$newOwner instanceof Player){
					$sender->sendMessage(TextFormat::RED . $this->translateString("give.notonline"));
					return;
				}elseif($newOwner->getName() === $sender->getName()){
					$sender->sendMessage(TextFormat::RED . $this->translateString("give.toself"));
					return;
				}
				$maxPlots = $this->plugin->getMaxPlotsOfPlayer($newOwner);
				if(count(yield $this->internalAPI->generatePlotsOfPlayer($newOwner->getName(), null)) >= $maxPlots){
					$sender->sendMessage(TextFormat::RED . $this->translateString("give.maxedout", [$maxPlots]));
					return;
				}
				if(count($args) < 2 or $args[1] !== $this->translateString("confirm")){
					$plotId = TextFormat::GREEN . $plot . TextFormat::WHITE;
					$newOwnerName = TextFormat::GREEN . $newOwner->getName() . TextFormat::WHITE;
					$sender->sendMessage($this->translateString("give.confirm", [$plotId, $newOwnerName]));
					return;
				}
				if(yield $this->internalAPI->generateClaimPlot($plot, $newOwner->getName(), '')){
					$plotId = TextFormat::GREEN . $plot . TextFormat::WHITE;
					$oldOwnerName = TextFormat::GREEN . $sender->getName() . TextFormat::WHITE;
					$newOwnerName = TextFormat::GREEN . $newOwner->getName() . TextFormat::WHITE;
					$sender->sendMessage($this->translateString("give.success", [$newOwnerName]));
					$newOwner->sendMessage($this->translateString("give.received", [$oldOwnerName, $plotId]));
				}else{
					$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
				}
			}
		);
		return true;
	}

	public function getFormClass() : ?string{
		return GiveForm::class;
	}
}