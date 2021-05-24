<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\DenyPlayerForm;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;

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
            $sender->sendMessage(C::RED."/p deny <Spieler>");
            return true;
		}
		$dplayer = $args[0];
		$plot = $this->getPlugin()->getPlotByPosition($sender);
		if($plot === null) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du stehst auf keinem Grundstück!");
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.denyplayer")) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du bist nicht Besitzer dieses Grundstücks!");
			return true;
		}
		if($dplayer === "*") {
			if($this->getPlugin()->addPlotDenied($plot, $dplayer)) {
                $sender->sendMessage(MyPlot::PREFIX."Du hast ".C::YELLOW."jeden".C::GRAY." von deinem Grundstück ".C::RED."gesperrt");
				foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $player) {
					if($this->getPlugin()->getPlotBB($plot)->isVectorInside($player) and !($player->getName() === $plot->owner) and !$player->hasPermission("myplot.admin.denyplayer.bypass") and !$plot->isHelper($player->getName()))
						$this->getPlugin()->teleportPlayerToPlot($player, $plot);
				}
			}else{
				$sender->sendMessage(C::RED . $this->translateString("error"));
			}
			return true;
		}
		$dplayer = $this->getPlugin()->getServer()->getPlayer($dplayer);
		if(!$dplayer instanceof Player) {
			$sender->sendMessage($this->translateString("denyplayer.notaplayer"));
			return true;
		}
		if($dplayer->hasPermission("myplot.admin.denyplayer.bypass") or $dplayer->getName() === $plot->owner) {
            $sender->sendMessage(MyPlot::PREFIX.C::RED."Du kannst diesen Spieler nicht sperren!");
            $dplayer->sendMessage(MyPlot::PREFIX.C::YELLOW.$sender->getName().C::RED." hat versucht dich von seinem Grundstück zu sperren.");
			return true;
		}
		if($this->getPlugin()->addPlotDenied($plot, $dplayer->getName())) {
            $sender->sendMessage(MyPlot::PREFIX."Du hast ".C::YELLOW.$dplayer->getName().C::GRAY." von deinem Grundstück ".C::RED."gesperrt");
            $dplayer->sendMessage(MyPlot::PREFIX."Du wurdest von dem Grundstück von ".$sender->getNameTag().C::GRAY."[".C::YELLOW.$plot->X.";".$plot->Z.C::GRAY."]".C::RED." gesperrt");
            if($this->getPlugin()->getPlotBB($plot)->isVectorInside($dplayer))
				$this->getPlugin()->teleportPlayerToPlot($dplayer, $plot);
		}else{
			$sender->sendMessage(C::RED . $this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($player !== null and ($plot = $this->getPlugin()->getPlotByPosition($player)) instanceof Plot)
			return new DenyPlayerForm($plot);
		return null;
	}
}