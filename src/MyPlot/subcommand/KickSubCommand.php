<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\KickForm;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;

class KickSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.kick");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if (!isset($args[0])){
            $sender->sendMessage(C::RED."/p kick <Spieler>");
            return true;
        }
		$plot = $this->getPlugin()->getPlotByPosition($sender);
		if($plot === null) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du stehst auf keinem Grundstück!");
			return true;
		}
		if ($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.kick")) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du bist nicht Besitzer dieses Grundstücks!");
			return true;
		}
		$target = $this->getPlugin()->getServer()->getPlayer($args[0]);
		if ($target === null) {
            $sender->sendMessage(MyPlot::PREFIX.C::RED."Der Spieler ist nicht online!");
			return true;
		}
		if (($targetPlot = $this->getPlugin()->getPlotByPosition($target)) === null or !$plot->isSame($targetPlot)) {
            $sender->sendMessage(MyPlot::PREFIX . C::RED . "Der Spieler steht nicht auf deinem Grundstück!");
			return true;
		}
		if ($target->hasPermission("myplot.admin.kick.bypass")) {
            $sender->sendMessage(MyPlot::PREFIX.C::RED."Du kannst diesen Spieler nicht kicken!");
            $sender->sendMessage(MyPlot::PREFIX.C::YELLOW.$sender->getName().C::RED." hat versucht dich von seinem Grundstück zu kicken.");
            return true;
		}
		if ($this->getPlugin()->teleportPlayerToPlot($target, $plot)) {
            $sender->sendMessage(MyPlot::PREFIX."Du hast ".C::YELLOW.$target->getName().C::GRAY." von deinem Grundstück ".C::RED."gekickt");
            $target->sendMessage(MyPlot::PREFIX."Du wurdest von dem Grundstück ".C::RED."gekickt");
			return true;
		}
		$sender->sendMessage($this->translateString("error"));
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($player !== null and $this->getPlugin()->getPlotByPosition($player) instanceof Plot)
			return new KickForm();
		return null;
	}
}
