<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\DenyPlayerForm;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class DenyPlayerSubCommand extends SubCommand
{
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
		if(count($args) === 0) {
			return false;
		}
		$dplayer = $args[0];
		$plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.denyplayer")) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		if($dplayer === "*") {
			if($this->getPlugin()->addPlotDenied($plot, $dplayer)) {
				$sender->sendMessage($this->translateString("denyplayer.success1", [$dplayer]));
				foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $player) {
					if($this->getPlugin()->getPlotBB($plot)->isVectorInside($player->getPosition()) and !($player->getName() === $plot->owner) and !$player->hasPermission("myplot.admin.denyplayer.bypass") and !$plot->isHelper($player->getName()))
						$this->getPlugin()->teleportPlayerToPlot($player, $plot);
					else {
						$sender->sendMessage($this->translateString("denyplayer.cannotdeny", [$player->getName()]));
						$player->sendMessage($this->translateString("denyplayer.attempteddeny", [$sender->getName()]));
					}
				}
			}else{
				$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
			}
			return true;
		}
		$dplayer = $this->getPlugin()->getServer()->getPlayerByPrefix($dplayer);
		if(!$dplayer instanceof Player) {
			$sender->sendMessage($this->translateString("denyplayer.notaplayer"));
			return true;
		}
		if($dplayer->hasPermission("myplot.admin.denyplayer.bypass") or $dplayer->getName() === $plot->owner) {
			$sender->sendMessage($this->translateString("denyplayer.cannotdeny", [$dplayer->getName()]));
			$dplayer->sendMessage($this->translateString("denyplayer.attempteddeny", [$sender->getName()]));
			return true;
		}
		if($this->getPlugin()->addPlotDenied($plot, $dplayer->getName())) {
			$sender->sendMessage($this->translateString("denyplayer.success1", [$dplayer->getName()]));
			$dplayer->sendMessage($this->translateString("denyplayer.success2", [$plot->X, $plot->Z, $sender->getName()]));
			if($this->getPlugin()->getPlotBB($plot)->isVectorInside($dplayer->getPosition()))
				$this->getPlugin()->teleportPlayerToPlot($dplayer, $plot);
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($player !== null and ($plot = $this->getPlugin()->getPlotByPosition($player->getPosition())) instanceof Plot)
			return new DenyPlayerForm($plot);
		return null;
	}
}