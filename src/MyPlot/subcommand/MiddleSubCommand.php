<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class MiddleSubCommand extends SubCommand
{
	/**
	 * @param CommandSender $sender
	 * @return bool
	 */
	public function canUse(CommandSender $sender) {
		return ($sender instanceof Player) and ($sender->hasPermission("myplot.command.middle"));
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) {
		if(count($args) != 0) {
			return false;
		}
		$plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
		if ($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if ($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.middle")) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		if($this->getPlugin()->teleportMiddle($sender, $plot)) {
			$sender->sendMessage(TextFormat::GREEN . $this->translateString("middle.success"));
		}
		return true;
	}
}