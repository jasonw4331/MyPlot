<?php
declare(strict_types=1);

namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

class DisposeSubCommand extends SubCommand{
	public function canUse(CommandSender $sender) : bool{
		if(!$sender->hasPermission("myplot.command.dispose")){
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
				$plot = yield from $this->internalAPI->generatePlotByPosition($sender->getPosition());
				if($plot === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
					return;
				}
				if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.dispose")){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
					return;
				}
				if(!isset($args[0]) or $args[0] !== $this->translateString("confirm")){
					$plotId = TextFormat::GREEN . $plot . TextFormat::WHITE;
					$sender->sendMessage($this->translateString("dispose.confirm", [$plotId]));
					return;
				}
				$economy = $this->internalAPI->getEconomyProvider();
				$price = $this->plugin->getLevelSettings($plot->levelName)->disposePrice;
				if($economy !== null and !(yield from $economy->reduceMoney($sender, $price))){
					$sender->sendMessage(TextFormat::RED . $this->translateString("dispose.nomoney"));
					return;
				}
				if(yield from $this->internalAPI->generateDisposePlot($plot)){
					$sender->sendMessage(TextFormat::GREEN . $this->translateString("dispose.success"));
				}else{
					$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
				}
			}
		);
		return true;
	}
}