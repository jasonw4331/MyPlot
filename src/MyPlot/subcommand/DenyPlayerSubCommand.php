<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class DenyPlayerSubCommand extends SubCommand
{
	/**
	 * @param CommandSender $sender
	 * @return bool
	 */
	public function canUse(CommandSender $sender) {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.denyplayer");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) {
		if (empty($args)) {
			return false;
		}
		$dplayer = strtolower($args[0]);
		$dp = $this->getPlugin()->getServer()->getPlayer($dplayer);
		$plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
		if ($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if ($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.denyplayer")) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $player) {
			if(similar_text($dplayer,strtolower($player->getName()))/strlen($player->getName()) >= 0.3 ) { //TODO correct with a better system
				$dplayer = $this->getPlugin()->getServer()->getPlayer($dplayer);
				break;
			}
		}
		if(!$dplayer instanceof Player) {
			$sender->sendMessage($this->translateString("denyplayer.notaplayer"));
			return true;
		}
		if($dplayer->hasPermission("myplot.admin.bypassdeny") or $dplayer->getName() == $plot->owner) {
			$sender->sendMessage($this->translateString("denyplayer.cannotdeny", [$dplayer->getName()]));
			if($dp instanceof Player)
				$dp->sendMessage($this->translateString("denyplayer.attempteddeny", [$sender->getName()]));
			return true;
		}
		if (!$plot->denyPlayer($dplayer->getName())) {
			$sender->sendMessage($this->translateString("denyplayer.alreadydenied", [$dplayer->getName()]));
			return true;
		}
		if ($this->getPlugin()->savePlot($plot)) {
			$sender->sendMessage($this->translateString("denyplayer.success1", [$dplayer->getName()]));
			if($dp instanceof Player)
				$dp->sendMessage($this->translateString("denyplayer.success2", [$plot->X,$plot->Z,$sender->getName()]));
		} else {
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}
}