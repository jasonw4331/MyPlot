<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\GenerateForm;
use MyPlot\MyPlotGenerator;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class GenerateSubCommand extends SubCommand
{
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
		if(count($args) === 0) {
			return false;
		}
		$levelName = $args[0];
		if($sender->getServer()->isLevelGenerated($levelName)) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("generate.exists", [$levelName]));
			return true;
		}
		if($this->getPlugin()->generateLevel($levelName, $args[2] ?? MyPlotGenerator::NAME)) {
			if(isset($args[1]) and $args[1] == true and $sender instanceof Player) {
				$this->getPlugin()->teleportPlayerToPlot($sender, new Plot($levelName, 0, 0));
			}
			$sender->sendMessage($this->translateString("generate.success", [$levelName]));
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("generate.error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return new GenerateForm();
	}
}