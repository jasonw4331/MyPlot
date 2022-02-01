<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\UndenyPlayerForm;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

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
			return false;
		}
		$dplayerName = $args[0];
		$plot = $this->plugin->getPlotByPosition($sender->getPosition());
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.undenyplayer")) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		$dplayer = $this->plugin->getServer()->getPlayerByPrefix($dplayerName);
		if($dplayer === null)
			$dplayer = $this->plugin->getServer()->getOfflinePlayer($dplayerName);
		if($this->plugin->removePlotDenied($plot, $dplayer->getName())) {
			$sender->sendMessage($this->translateString("undenyplayer.success1", [$dplayer->getName()]));
			if($dplayer instanceof Player) {
				$dplayer->sendMessage($this->translateString("undenyplayer.success2", [$plot->X, $plot->Z, $sender->getName()]));
			}
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($player !== null and ($plot = $this->plugin->getPlotByPosition($player->getPosition())) instanceof Plot)
			return new UndenyPlayerForm($plot);
		return null;
	}
}