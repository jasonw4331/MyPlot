<?php
declare(strict_types=1);

namespace MyPlot\subcommand;

use MyPlot\forms\subforms\KickForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

class KickSubCommand extends SubCommand{
	public function canUse(CommandSender $sender) : bool{
		if(!$sender->hasPermission("myplot.command.kick")){
			return false;
		}
		if($sender instanceof Player){
			$pos = $sender->getPosition();
			$plotLevel = $this->internalAPI->getLevelSettings($sender->getWorld()->getFolderName());
			if($this->internalAPI->getPlotFast($pos->x, $pos->z, $plotLevel) === null){
				return false;
			}
		}

		return true;
	}

	/**
	 * @param Player   $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool{
		Await::f2c(
			function() use ($sender, $args) : \Generator{
				if(!isset($args[0])){
					$sender->sendMessage($this->translateString("subcommand.usage", [$this->getUsage()]));
					return;
				}
				$plot = yield from $this->internalAPI->generatePlotByPosition($sender->getPosition());
				if($plot === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
					return;
				}
				if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.kick")){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
					return;
				}
				$target = $this->plugin->getServer()->getPlayerByPrefix($args[0]);
				if($target === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("kick.noPlayer"));
					return;
				}
				if(($targetPlot = yield from $this->internalAPI->generatePlotByPosition($target->getPosition())) === null or !$plot->isSame($targetPlot)){
					$sender->sendMessage(TextFormat::RED . $this->translateString("kick.notInPlot"));
					return;
				}
				if($target->hasPermission("myplot.admin.kick.bypass")){
					$sender->sendMessage(TextFormat::RED . $this->translateString("kick.cannotkick"));
					$target->sendMessage($this->translateString("kick.attemptkick", [$target->getName()]));
					return;
				}
				if($this->internalAPI->teleportPlayerToPlot($target, $plot, false)){
					$sender->sendMessage($this->translateString("kick.success1", [$target->getName(), $plot->__toString()]));
					$target->sendMessage($this->translateString("kick.success2", [$sender->getName(), $plot->__toString()]));
				}else{
					$sender->sendMessage($this->translateString("error"));
				}
			}
		);
		return true;
	}

	public function getFormClass() : ?string{
		return KickForm::class;
	}
}
