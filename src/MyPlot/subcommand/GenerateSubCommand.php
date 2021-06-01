<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\GenerateForm;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class GenerateSubCommand extends SubCommand
{
	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
	public function canUse(CommandSender $sender) : bool {
		return $sender->hasPermission("myplot.command.generate");
	}

	/**
	 * @param CommandSender $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(empty($args)) {
			return false;
		}
		$worldName = $args[0];
		if($sender->getServer()->getWorldManager()->isWorldGenerated($worldName)) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("generate.exists", [$worldName]));
			return true;
		}
		if($this->getPlugin()->generateLevel($worldName, $args[2] ?? "myplot")) {
			if(isset($args[1]) and $args[1] == true and $sender instanceof Player) {
				$this->getPlugin()->teleportPlayerToPlot($sender, new Plot($worldName, 0, 0));
			}
			$sender->sendMessage($this->translateString("generate.success", [$worldName]));
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("generate.error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return new GenerateForm();
	}
}