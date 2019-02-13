<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class UnDenySubCommand extends SubCommand
{
	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.undenyplayer");
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
		$dplayer = $args[0];
		$plot = $this->getPlugin()->getPlotByPosition($sender);
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.undenyplayer")) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		if(!$plot->unDenyPlayer($dplayer)) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("undenyplayer.failure", [$dplayer]));
			return true;
		}
		$dplayer = $this->getPlugin()->getServer()->getPlayer($dplayer) ?? $this->getPlugin()->getServer()->getOfflinePlayer($dplayer);
		if($this->getPlugin()->savePlot($plot)) {
			$sender->sendMessage($this->translateString("undenyplayer.success1", [$dplayer->getName()]));
			if($dplayer instanceof Player) {
				$dplayer->sendMessage($this->translateString("undenyplayer.success2", [$plot->X, $plot->Z, $sender->getName()]));
			}
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}
}