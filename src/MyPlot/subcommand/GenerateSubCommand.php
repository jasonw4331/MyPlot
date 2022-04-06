<?php
declare(strict_types=1);

namespace MyPlot\subcommand;

use MyPlot\forms\subforms\GenerateForm;
use MyPlot\MyPlotGenerator;
use MyPlot\plot\BasePlot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class GenerateSubCommand extends SubCommand{
	public function canUse(CommandSender $sender) : bool{
		return $sender->hasPermission("myplot.command.generate");
	}

	/**
	 * @param CommandSender $sender
	 * @param string[]      $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool{
		if(count($args) === 0){
			return false;
		}
		$levelName = $args[0];
		if($sender->getServer()->getWorldManager()->isWorldGenerated($levelName)){
			$sender->sendMessage(TextFormat::RED . $this->translateString("generate.exists", [$levelName]));
			return true;
		}
		if($this->plugin->generateLevel($levelName, $args[2] ?? MyPlotGenerator::NAME)){
			if(isset($args[1]) and $args[1] == true and $sender instanceof Player){
				$this->internalAPI->teleportPlayerToPlot($sender, new BasePlot($levelName, 0, 0), false);
			}
			$sender->sendMessage($this->translateString("generate.success", [$levelName]));
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("generate.error"));
		}
		return true;
	}

	public function getFormClass() : ?string{
		return GenerateForm::class;
	}
}