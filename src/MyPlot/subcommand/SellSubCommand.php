<?php
declare(strict_types=1);

namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

class SellSubCommand extends SubCommand{
	public function canUse(CommandSender $sender) : bool{
		if(!$sender->hasPermission("myplot.command.sell")){
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
				$plot = yield from $this->internalAPI->generatePlotByPosition($sender->getPosition());
				if($plot === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
					return;
				}
				if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.sell")){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
					return;
				}
				if(!is_numeric($args[0])){
					$sender->sendMessage($this->translateString("subcommand.usage", [$this->getUsage()]));
					return;
				}
				$price = (int) $args[0];
				if($price <= 0){
					$sender->sendMessage(TextFormat::RED . $this->translateString("sell.unlisted", ["$plot->X;$plot->Z"]));
					return;
				}
				if(yield from $this->internalAPI->generateSellPlot($plot, $price)){
					$sender->sendMessage($this->translateString("sell.success", ["$plot->X;$plot->Z", $price]));
				}else{
					$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
				}
			}
		);
		return true;
	}
}
