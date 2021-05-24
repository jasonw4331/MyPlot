<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\AddHelperForm;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\OfflinePlayer;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;

class AddHelperSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.addhelper");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(count($args) === 0) {
            $sender->sendMessage(C::RED."/p trust <Spieler>");
            return true;
		}
		$helperName = $args[0];
		$plot = $this->getPlugin()->getPlotByPosition($sender);
		if($plot === null) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du stehst auf keinem Grundstück!");
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.addhelper")) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du bist nicht Besitzer dieses Grundstücks!");
			return true;
		}
		$helper = $this->getPlugin()->getServer()->getPlayer($helperName);
		if($helper === null)
			$helper = new OfflinePlayer($this->getPlugin()->getServer(), $helperName);
		if($this->getPlugin()->addPlotHelper($plot, $helper->getName())) {
            $sender->sendMessage(MyPlot::PREFIX . "Du hast ".C::YELLOW.$helper->getName().C::GRAY." zu deinem Grundstück als Helfer ".C::GREEN."hinzugefügt");
		}else{
            $sender->sendMessage(MyPlot::PREFIX . C::YELLOW.$helperName.C::RED." ist bereits Helfer auf dem Grundstück!");
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($player !== null and ($plot = $this->getPlugin()->getPlotByPosition($player)) instanceof Plot)
			return new AddHelperForm($plot);
		return null;
	}
}