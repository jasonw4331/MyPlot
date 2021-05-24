<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\RemoveHelperForm;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\OfflinePlayer;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;

class RemoveHelperSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.removehelper");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(count($args) === 0) {
            $sender->sendMessage(C::RED."/p remove <Spieler>");
            return true;
		}
		$helperName = $args[0];
		$plot = $this->getPlugin()->getPlotByPosition($sender);
		if($plot === null) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du stehst auf keinem Grundstück!");
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.removehelper")) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du bist nicht Besitzer dieses Grundstücks!");
			return true;
		}
		$helper = $this->getPlugin()->getServer()->getPlayer($helperName);
		if($helper === null)
			$helper = new OfflinePlayer($this->getPlugin()->getServer(), $helperName);
		if($this->getPlugin()->removePlotHelper($plot, $helper->getName())) {
            $sender->sendMessage(MyPlot::PREFIX . "Du hast ".C::YELLOW.$helper->getName().C::GRAY." von deinem Grundstück als Helfer ".C::RED."entfernt");
		}else{
            $sender->sendMessage(MyPlot::PREFIX . C::YELLOW.$helperName.C::RED." ist auf dem Grundstück kein Helfer!");
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($player !== null and $this->getPlugin()->getPlotByPosition($player) instanceof Plot)
			return new RemoveHelperForm();
		return null;
	}
}