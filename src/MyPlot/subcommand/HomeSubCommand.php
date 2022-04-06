<?php
declare(strict_types=1);

namespace MyPlot\subcommand;

use MyPlot\forms\subforms\HomeForm;
use MyPlot\plot\BasePlot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

class HomeSubCommand extends SubCommand{
	public function canUse(CommandSender $sender) : bool{
		if(!$sender->hasPermission("myplot.command.home")){
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
					$plotNumber = 1;
				}elseif(is_numeric($args[0])){
					$plotNumber = (int) $args[0];
				}else{
					$sender->sendMessage($this->translateString("subcommand.usage", [$this->getUsage()]));
					return;
				}
				$levelName = $args[1] ?? $sender->getWorld()->getFolderName();
				if($this->internalAPI->getLevelSettings($levelName) === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("error", [$levelName]));
					return;
				}
				$plots = yield from $this->internalAPI->generatePlotsOfPlayer($sender->getName(), $levelName);
				if(count($plots) === 0){
					$sender->sendMessage(TextFormat::RED . $this->translateString("home.noplots"));
					return;
				}
				if(!isset($plots[$plotNumber - 1])){
					$sender->sendMessage(TextFormat::RED . $this->translateString("home.notexist", [$plotNumber]));
					return;
				}
				usort($plots, function(BasePlot $plot1, BasePlot $plot2){
					if($plot1->levelName == $plot2->levelName){
						return 0;
					}
					return ($plot1->levelName < $plot2->levelName) ? -1 : 1;
				});
				$plot = $plots[$plotNumber - 1];
				if($this->internalAPI->teleportPlayerToPlot($sender, $plot, false)){
					$sender->sendMessage($this->translateString("home.success", [$plot->__toString(), $plot->levelName]));
				}else{
					$sender->sendMessage(TextFormat::RED . $this->translateString("home.error"));
				}
			}
		);
		return true;
	}

	public function getFormClass() : ?string{
		return HomeForm::class;
	}
}