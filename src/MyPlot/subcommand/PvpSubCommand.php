<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class PvpSubCommand extends SubCommand {

	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.pvp");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		$plot = $this->getPlugin()->getPlotByPosition($sender);
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED.$this->translateString("notinplot"));
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.pvp")) {
			$sender->sendMessage(TextFormat::RED.$this->translateString("notowner"));
			return true;
		}
		$levelSettings = $this->getPlugin()->getLevelSettings($sender->level->getFolderName());
		if($levelSettings->restrictPVP) {
			$sender->sendMessage(TextFormat::RED.$this->translateString("pvp.world"));
			return true;
		}
		$plot->pvp = !$plot->pvp;
		if($this->getPlugin()->savePlot($plot)) {
			$sender->sendMessage($this->translateString("pvp.success", [$plot->pvp ? "enabled" : "disabled"]));
		}else {
			$sender->sendMessage(TextFormat::RED.$this->translateString("error"));
		}
		return true;
	}
}