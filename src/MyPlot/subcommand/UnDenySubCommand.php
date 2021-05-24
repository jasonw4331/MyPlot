<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\UndenyPlayerForm;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\OfflinePlayer;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;

class UnDenySubCommand extends SubCommand
{
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
		if(count($args) === 0) {
            $sender->sendMessage(C::RED."/p undeny <Spieler>");
            return true;
		}
		$dplayerName = $args[0];
		$plot = $this->getPlugin()->getPlotByPosition($sender);
		if($plot === null) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du stehst auf keinem Grundstück!");
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.undenyplayer")) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du bist nicht Besitzer dieses Grundstücks!");
			return true;
		}
		$dplayer = $this->getPlugin()->getServer()->getPlayer($dplayerName);
		if($dplayer === null)
			$dplayer = new OfflinePlayer($this->getPlugin()->getServer(), $dplayerName);
		if($this->getPlugin()->removePlotDenied($plot, $dplayer->getName())) {
            $sender->sendMessage(MyPlot::PREFIX . C::GREEN . "Du hast erfolgreich ".C::YELLOW.($dplayerName === "*" ? "*" : $dplayer->getName()).C::GREEN." von deinem Grundstück entsperrt.");
			if($dplayer instanceof Player) {
                $dplayer->sendMessage(MyPlot::PREFIX . C::GREEN . "Du wurdest wieder von dem Grundstück von ".C::YELLOW.$sender->getName().C::GRAY."(".C::YELLOW.$plot->X.";".$plot->Z.C::GRAY.")".C::GREEN." entsperrt.");
			}
		}else{
			$sender->sendMessage(C::RED . $this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($player !== null and $this->getPlugin()->getPlotByPosition($player) instanceof Plot)
			return new UndenyPlayerForm();
		return null;
	}
}