<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\HomeForm;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class HomeSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.home");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(count($args) === 0) {
			$plotNumber = 1;
		}elseif(is_numeric($args[0])) {
			$plotNumber = (int) $args[0];
		}else{
			return false;
		}
		$levelName = $args[1] ?? $sender->getWorld()->getFolderName();
		if(!$this->plugin->isLevelLoaded($levelName)) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("error", [$levelName]));
			return true;
		}
		$plots = $this->plugin->getPlotsOfPlayer($sender->getName(), $levelName);
		if(count($plots) === 0) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("home.noplots"));
			return true;
		}
		if(!isset($plots[$plotNumber - 1])) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("home.notexist", [$plotNumber]));
			return true;
		}
		usort($plots, function(Plot $plot1, Plot $plot2) {
			if($plot1->levelName == $plot2->levelName) {
				return 0;
			}
			return ($plot1->levelName < $plot2->levelName) ? -1 : 1;
		});
		$plot = $plots[$plotNumber - 1];
		if($this->plugin->teleportPlayerToPlot($sender, $plot)) {
			$sender->sendMessage($this->translateString("home.success", [$plot->__toString(), $plot->levelName]));
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("home.error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($player !== null and count($this->plugin->getPlotsOfPlayer($player->getName(), $player->getWorld()->getFolderName())) > 0)
			return new HomeForm($player);
		return null;
	}
}