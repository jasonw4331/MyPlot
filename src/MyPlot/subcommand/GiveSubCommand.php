<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class GiveSubCommand extends SubCommand
{
	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.give");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(empty($args)) {
			return false;
		}
		$newOwner = $args[0];
		$plot = $this->getPlugin()->getPlotByPosition($sender);
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if($plot->owner !== $sender->getName()) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		$newOwner = $this->getPlugin()->getServer()->getPlayer($newOwner);
		if(!$newOwner instanceof Player) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("give.notonline"));
			return true;
		}elseif($newOwner->getName() === $sender->getName()) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("give.toself"));
			return true;
		}
		$maxPlots = $this->getPlugin()->getMaxPlotsOfPlayer($newOwner);
		$plotsOfPlayer = count($this->getPlugin()->getPlotsOfPlayer($newOwner->getName(), $newOwner->getLevel()->getFolderName()));
		if($plotsOfPlayer >= $maxPlots) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("give.maxedout", [$maxPlots]));
			return true;
		}
		if(count($args) == 2 and $args[1] == $this->translateString("confirm")) {
			$plot->owner = $newOwner->getName();
			if($this->getPlugin()->savePlot($plot)) {
				$plotId = TextFormat::GREEN . $plot . TextFormat::WHITE;
				$oldOwnerName = TextFormat::GREEN . $sender->getName() . TextFormat::WHITE;
				$newOwnerName = TextFormat::GREEN . $newOwner->getName() . TextFormat::WHITE;
				$sender->sendMessage($this->translateString("give.success", [$newOwnerName]));
				$newOwner->sendMessage($this->translateString("give.received", [$oldOwnerName, $plotId]));
			}else{
				$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
			}
		}else{
			$plotId = TextFormat::GREEN . $plot . TextFormat::WHITE;
			$newOwnerName = TextFormat::GREEN . $newOwner->getName() . TextFormat::WHITE;
			$sender->sendMessage($this->translateString("give.confirm", [$plotId, $newOwnerName]));
		}
		return true;
	}
}