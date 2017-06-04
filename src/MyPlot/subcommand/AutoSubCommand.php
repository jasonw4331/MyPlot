<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class AutoSubCommand extends SubCommand
{
	/**
	 * @param CommandSender $sender
	 * @return bool
	 */
	public function canUse(CommandSender $sender) {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.auto");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) {
		if (!empty($args)) {
			return false;
		}
		$levelName = $sender->getLevel()->getName();
		if (!$this->getPlugin()->isLevelLoaded($levelName)) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("auto.notplotworld"));
			return true;
		}
		if (($plot = $this->getPlugin()->getNextFreePlot($levelName)) !== null) {
			$this->getPlugin()->teleportPlayerToPlot($sender, $plot);
			$sender->sendMessage($this->translateString("auto.success", [$plot->X, $plot->Z]));
		} else {
			$sender->sendMessage(TextFormat::RED . $this->translateString("auto.noplots"));
		}
		return true;
	}
}