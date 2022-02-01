<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\CloneForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class CloneSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.clone");
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
		/** @var string[] $plotIdArray */
		$plotIdArray = explode(";", $args[0]);
		if(count($plotIdArray) != 2 or !is_numeric($plotIdArray[0]) or !is_numeric($plotIdArray[1])) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("clone.wrongid"));
			return true;
		}
		$levelName = $args[1] ?? $sender->getWorld()->getFolderName();
		$selectedPlot = $this->plugin->getProvider()->getPlot($levelName, (int) $plotIdArray[0], (int) $plotIdArray[1]);
		$standingPlot = $this->plugin->getPlotByPosition($sender->getPosition());
		if($standingPlot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if($standingPlot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.clone")) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		if($selectedPlot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.clone")) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		$plotLevel = $this->plugin->getLevelSettings($standingPlot->levelName);
		$economy = $this->plugin->getEconomyProvider();
		if($economy !== null and !$economy->reduceMoney($sender, $plotLevel->clonePrice)) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("clone.nomoney"));
			return true;
		}
		if($this->plugin->clonePlot($selectedPlot, $standingPlot)) {
			$sender->sendMessage($this->translateString("clone.success", [$selectedPlot->__toString(), $standingPlot->__toString()]));
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
			return $player !== null ? new CloneForm($player) : null;
	}
}