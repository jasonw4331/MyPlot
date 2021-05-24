<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\MyPlot;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;

class MiddleSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and ($sender->hasPermission("myplot.command.middle"));
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(count($args) != 0) {
			return false;
		}
		$plot = $this->getPlugin()->getPlotByPosition($sender);
		if($plot === null) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du stehst auf keinem Grundstück!");
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.middle")) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du bist nicht Besitzer dieses Grundstücks!");
			return true;
		}
		if($this->getPlugin()->teleportPlayerToPlot($sender, $plot, true)) {
            $sender->sendMessage(MyPlot::PREFIX . C::GREEN . "Du wurdest in die Mitte des Grundstücks teleportiert.");
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return null;
	}
}