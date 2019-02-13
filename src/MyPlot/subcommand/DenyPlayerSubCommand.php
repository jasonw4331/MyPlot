<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class DenyPlayerSubCommand extends SubCommand
{
	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.denyplayer");
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
		$dplayer = strtolower($args[0]);
		$plot = $this->getPlugin()->getPlotByPosition($sender);
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.denyplayer")) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		$dplayer = $this->getPlugin()->getServer()->getPlayer($dplayer) ?? $this->getPlugin()->getServer()->getOfflinePlayer($dplayer);
		if(!$dplayer instanceof Player) {
			$sender->sendMessage($this->translateString("denyplayer.notaplayer"));
			return true;
		}
		if($dplayer->hasPermission("myplot.admin.denyplayer.bypass") or $dplayer->getName() === $plot->owner) {
			$sender->sendMessage($this->translateString("denyplayer.cannotdeny", [$dplayer->getName()]));
			if($dplayer instanceof Player)
				$dplayer->sendMessage($this->translateString("denyplayer.attempteddeny", [$sender->getName()]));
			return true;
		}
		if(!$plot->denyPlayer($dplayer->getName())) {
			$sender->sendMessage($this->translateString("denyplayer.alreadydenied", [$dplayer->getName()]));
			return true;
		}
		if($this->getPlugin()->savePlot($plot)) {
			$sender->sendMessage($this->translateString("denyplayer.success1", [$dplayer->getName()]));
			if($dplayer instanceof Player) {
				$dplayer->sendMessage($this->translateString("denyplayer.success2", [$plot->X, $plot->Z, $sender->getName()]));
			}
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}
}