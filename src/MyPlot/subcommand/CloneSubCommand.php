<?php
declare(strict_types=1);

namespace MyPlot\subcommand;

use MyPlot\forms\subforms\CloneForm;
use MyPlot\plot\BasePlot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

class CloneSubCommand extends SubCommand{
	public function canUse(CommandSender $sender) : bool{
		if(!$sender->hasPermission("myplot.command.clone")){
			return false;
		}
		if($sender instanceof Player){
			$pos = $sender->getPosition();
			$plotLevel = $this->internalAPI->getLevelSettings($sender->getWorld()->getFolderName());
			if($this->internalAPI->getPlotFast($pos->x, $pos->z, $plotLevel) === null){
				return false;
			}
		}

		return true;
	}

	/**
	 * @param Player   $sender
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
				$plotIdArray = explode(";", $args[0]);
				if(count($plotIdArray) < 2 or !is_numeric($plotIdArray[0]) or !is_numeric($plotIdArray[1])){
					$sender->sendMessage(TextFormat::RED . $this->translateString("clone.wrongid"));
					return;
				}
				$levelName = $args[1] ?? $sender->getWorld()->getFolderName();
				$selectedPlot = yield from $this->internalAPI->generatePlot(new BasePlot($levelName, (int) $plotIdArray[0], (int) $plotIdArray[1]));
				$standingPlot = yield from $this->internalAPI->generatePlotByPosition($sender->getPosition());
				if($standingPlot === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
					return;
				}
				if($standingPlot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.clone")){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
					return;
				}
				if($selectedPlot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.clone")){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
					return;
				}
				$plotLevel = $this->plugin->getLevelSettings($standingPlot->levelName);
				$economy = $this->internalAPI->getEconomyProvider();
				if($economy !== null and !(yield from $economy->reduceMoney($sender, $plotLevel->clonePrice))){
					$sender->sendMessage(TextFormat::RED . $this->translateString("clone.nomoney"));
					return;
				}
				if($this->internalAPI->clonePlot($selectedPlot, $standingPlot)){
					$sender->sendMessage($this->translateString("clone.success", [$selectedPlot->__toString(), $standingPlot->__toString()]));
				}else{
					$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
				}
			}
		);
		return true;
	}

	public function getFormClass() : ?string{
		return CloneForm::class;
	}
}