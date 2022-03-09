<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\GenerateForm;
use MyPlot\MyPlotGenerator;
use MyPlot\plot\BasePlot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

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
	public function execute(CommandSender $sender, array $args) : bool{
		Await::f2c(
			function() use ($sender, $args) : \Generator{
				if(count($args) === 0){
					$sender->sendMessage($this->translateString("subcommand.usage", [$this->getUsage()]));
					return;
				}
				$levelName = $args[0];
				if($sender->getServer()->getWorldManager()->isWorldGenerated($levelName)){
					$sender->sendMessage(TextFormat::RED . $this->translateString("generate.exists", [$levelName]));
					return;
				}
				if($this->plugin->generateLevel($levelName, $args[2] ?? MyPlotGenerator::NAME)){
					if(isset($args[1]) and $args[1] == true and $sender instanceof Player){
						yield $this->internalAPI->generatePlayerTeleport($sender, new BasePlot($levelName, 0, 0), false);
					}
					$sender->sendMessage($this->translateString("generate.success", [$levelName]));
				}else{
					$sender->sendMessage(TextFormat::RED . $this->translateString("generate.error"));
				}
			}
		);
		return true;
	}

	public function getFormClass() : ?string{
		return GenerateForm::class;
	}
}