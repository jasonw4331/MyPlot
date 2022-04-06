<?php
declare(strict_types=1);

namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

class ClearSubCommand extends SubCommand{
	public function canUse(CommandSender $sender) : bool{
		if(!$sender->hasPermission("myplot.command.clear")){
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
				if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.clear")){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
					return;
				}
				if(!isset($args[0]) or $args[0] !== $this->translateString("confirm")){
					$plotId = TextFormat::GREEN . $plot . TextFormat::WHITE;
					$sender->sendMessage($this->translateString("clear.confirm", [$plotId]));
					return;
				}
				$economy = $this->internalAPI->getEconomyProvider();
				$price = $this->internalAPI->getLevelSettings($plot->levelName)->clearPrice;
				if($economy !== null and !(yield from $economy->reduceMoney($sender, $price, 'used plot clear command'))){
					$sender->sendMessage(TextFormat::RED . $this->translateString("clear.nomoney"));
					return;
				}
				$maxBlocksPerTick = $this->plugin->getConfig()->get("ClearBlocksPerTick", 256);
				if(!is_int($maxBlocksPerTick))
					$maxBlocksPerTick = 256;
				if(yield from $this->internalAPI->generateClearPlot($plot, $maxBlocksPerTick)){
					$sender->sendMessage($this->translateString("clear.success"));
				}else{
					$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
				}
			}
		);
		return true;
	}
}