<?php
declare(strict_types=1);

namespace MyPlot\subcommand;

use MyPlot\forms\subforms\AddHelperForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

class AddHelperSubCommand extends SubCommand{
	public function canUse(CommandSender $sender) : bool{
		if(!$sender->hasPermission("myplot.command.addhelper")){
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
				$helperName = $args[0];
				$plot = yield from $this->internalAPI->generatePlotByPosition($sender->getPosition());
				if($plot === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
					return;
				}
				if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.addhelper")){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
					return;
				}
				$helper = $this->plugin->getServer()->getPlayerByPrefix($helperName);
				if($helper === null)
					$helper = $this->plugin->getServer()->getOfflinePlayer($helperName);
				if(yield from $this->internalAPI->generateAddPlotHelper($plot, $helper->getName())){
					$sender->sendMessage($this->translateString("addhelper.success", [$helper->getName()]));
				}else{
					$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
				}
			}
		);
		return true;
	}

	public function getFormClass() : ?string{
		return AddHelperForm::class;
	}
}