<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\MyPlot;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;

class SellSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.sell");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(count($args) === 0) {
			return false;
		}
		$plot = $this->getPlugin()->getPlotByPosition($sender->asPosition());
		if($plot === null){
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du stehst auf keinem Grundstück!");
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.sell")){
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du bist nicht Besitzer dieses Grundstücks!");
			return true;
		}
		if(!is_numeric($args[0]))
			return false;
		$price = (float)$args[0];
		if($this->getPlugin()->sellPlot($plot, $price) and $price > 0) {
			$sender->sendMessage($this->translateString("sell.success", ["{$plot->X};{$plot->Z}", $price]));
		}elseif($price <= 0){
			$sender->sendMessage(C::RED . $this->translateString("sell.unlisted", ["{$plot->X};{$plot->Z}"]));
		}else{
			$sender->sendMessage(C::RED . $this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		// TODO: Implement getForm() method.
		return null;
	}
}
