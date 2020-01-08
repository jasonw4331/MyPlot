<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\OfflinePlayer;
use pocketmine\Player;
use pocketmine\Server;
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
		$dplayer = $args[0];
		$plot = $this->getPlugin()->getPlotByPosition($sender);
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.denyplayer")) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		if($dplayer === "*") {
			$dplayer = new OfflinePlayer(Server::getInstance(), "*");
			GOTO STAR;
		}
		$dplayer = $this->getPlugin()->getServer()->getPlayer($dplayer);
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
		STAR:
		if($this->getPlugin()->addPlotDenied($plot, $dplayer->getName())) {
			$sender->sendMessage($this->translateString("denyplayer.success1", [$dplayer->getName()]));
			if($dplayer instanceof Player) {
				$dplayer->sendMessage($this->translateString("denyplayer.success2", [$plot->X, $plot->Z, $sender->getName()]));
			}
			if($dplayer->getName() === "*") {
				foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $player) {
					if($this->getPlugin()->getPlotBB($plot)->isVectorInside($player) and !($player->getName() === $plot->owner) and !$plot->isHelper($player->getName()))
						$this->getPlugin()->teleportPlayerToPlot($player, $plot);
				}
			}elseif($this->getPlugin()->getPlotBB($plot)->isVectorInside($dplayer))
				$this->getPlugin()->teleportPlayerToPlot($dplayer, $plot);
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}
}