<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class SetOwnerSubCommand extends SubCommand {
	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.admin.setowner");
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
		$plot = $this->getPlugin()->getPlotByPosition($sender);
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		$maxPlots = $this->getPlugin()->getMaxPlotsOfPlayer($sender);
		$plotsOfPlayer = 0;
		foreach($this->getPlugin()->getPlotLevels() as $level => $settings) {
			$level = $this->getPlugin()->getServer()->getLevelByName($level);
			if(!$level->isClosed()) {
				$plotsOfPlayer += count($this->getPlugin()->getPlotsOfPlayer($sender->getName(), $level->getFolderName()));
			}
		}
		if($plotsOfPlayer >= $maxPlots) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("setowner.maxplots", [$maxPlots]));
			return true;
		}
		$plot->owner = $args[0];
		$plot->name = "";
		if($this->getPlugin()->savePlot($plot)) {
			$sender->sendMessage($this->translateString("setowner.success", [$plot->owner]));
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}
}