<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\RemoveHelperForm;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

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
			return false;
		}
		$helperName = $args[0];
		$plot = $this->plugin->getPlotByPosition($sender->getPosition());
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.removehelper")) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		$helper = $this->plugin->getServer()->getPlayerByPrefix($helperName);
		if($helper === null)
			$helper = $this->plugin->getServer()->getOfflinePlayer($helperName);
		if($this->plugin->removePlotHelper($plot, $helper->getName())) {
			$sender->sendMessage($this->translateString("removehelper.success", [$helper->getName()]));
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($player !== null and ($plot = $this->plugin->getPlotByPosition($player->getPosition())) instanceof Plot)
			return new RemoveHelperForm($plot);
		return null;
	}
}