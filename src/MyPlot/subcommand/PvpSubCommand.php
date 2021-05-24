<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\MyPlot;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;

class PvpSubCommand extends SubCommand {

	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.pvp");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		$plot = $this->getPlugin()->getPlotByPosition($sender);
		if($plot === null) {
            $sender->sendMessage(MyPlot::PREFIX . C::RED . "Du stehst auf keinem Grundstück!");
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.pvp")) {
            $sender->sendMessage(MyPlot::PREFIX . C::RED . "Du bist nicht Besitzer dieses Grundstücks!");
			return true;
		}
		$levelSettings = $this->getPlugin()->getLevelSettings($sender->getLevelNonNull()->getFolderName());
		if($levelSettings->restrictPVP) {
            $sender->sendMessage(MyPlot::PREFIX . C::RED."PvP ist in dieser Welt deaktiviert.");
			return true;
		}
		if($this->getPlugin()->setPlotPvp($plot, !$plot->pvp)) {
            $sender->sendMessage(MyPlot::PREFIX."Du hast auf deinem Grundstück PvP ".(!$plot->pvp ? C::GREEN."aktiviert" : C::RED."deaktiviert"));
            if(!$plot->pvp)
                foreach(Server::getInstance()->getOnlinePlayers() as $player)
                    if(($playerPlot = MyPlot::getInstance()->getPlotByPosition($player)) !== null && $playerPlot->isSame($plot)){
                        $this->getPlugin()->teleportPlayerToPlot($player, $plot);
                        $player->sendTitle(C::RED."Achtung!", C::RED."PvP ist auf diesem Grundstück nun aktiviert");
                    }
		}else {
			$sender->sendMessage(C::RED.$this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return null;
	}
}