<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class CopySubCommand extends SubCommand
{
	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.copy");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(empty($args)) {
			return false;
		}
		/** @var string[] $plotIdArray */
		$plotIdArray = explode(";", $args[0]);
		if(count($plotIdArray) != 2 or !is_numeric($plotIdArray[0]) or !is_numeric($plotIdArray[1])) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("copy.wrongid"));
			return true;
		}
		$levelName = $args[1] ?? $sender->getLevel()->getFolderName();
		$pastePlot = $this->getPlugin()->getProvider()->getPlot($levelName, (int) $plotIdArray[0], (int) $plotIdArray[1]);
		$copyPlot = $this->getPlugin()->getPlotByPosition($sender);
		if($copyPlot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if($copyPlot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.copy")) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		// TODO
		$plotLevel = $this->getPlugin()->getLevelSettings($copyPlot->levelName);
		$economy = $this->getPlugin()->getEconomyProvider();
		if($economy !== null and !$economy->reduceMoney($sender, $plotLevel->copyPrice)) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("copy.nomoney"));
			return true;
		}
		if($this->getPlugin()->copyPlot($copyPlot, $pastePlot)) {
			$sender->sendMessage($this->translateString("copy.success"));
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}
}