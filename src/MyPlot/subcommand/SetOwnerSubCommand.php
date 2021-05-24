<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\events\MyPlotOwnerChangeEvent;
use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\OwnerForm;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;

class SetOwnerSubCommand extends SubCommand {
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.admin.setowner");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(count($args) === 0) {
            $sender->sendMessage(C::RED."/p setowner <Spieler>");
            return true;
		}
		$plot = $this->getPlugin()->getPlotByPosition($sender);
		if($plot === null) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du stehst auf keinem Grundstück!");
			return true;
		}
		if($this->getPlugin()->claimPlot($plot, $args[0])) {
            $sender->sendMessage(MyPlot::PREFIX . C::GREEN . "Das Grundstück gehört nun ".C::YELLOW.$args[0]);
            $event = new MyPlotOwnerChangeEvent($plot, $sender->getName(), $args[0]);
            $event->call();
        }else{
			$sender->sendMessage(C::RED . $this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($player !== null and $this->getPlugin()->getPlotByPosition($player) instanceof Plot)
			return new OwnerForm();
		return null;
	}
}