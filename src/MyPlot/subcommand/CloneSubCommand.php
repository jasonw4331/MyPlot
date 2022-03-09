<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\CloneForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

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
				$selectedPlot = yield $this->internalAPI->generatePlot($levelName, (int) $plotIdArray[0], (int) $plotIdArray[1]);
				$standingPlot = yield $this->internalAPI->generatePlotByPosition($sender->getPosition());
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
				if($economy !== null and !(yield $economy->reduceMoney($sender, $plotLevel->clonePrice))){
					$sender->sendMessage(TextFormat::RED . $this->translateString("clone.nomoney"));
					return;
				}
				if(yield $this->internalAPI->generateClonePlot($selectedPlot, $standingPlot)){
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