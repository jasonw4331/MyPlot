<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

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
		$plot = $this->plugin->getPlotByPosition($sender->getPosition());
		if($plot === null){
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.sell")){
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		if(!is_numeric($args[0]))
			return false;
		$price = (float)$args[0];
		if($this->plugin->sellPlot($plot, $price) and $price > 0) {
			$sender->sendMessage($this->translateString("sell.success", ["$plot->X;$plot->Z", $price]));
		}elseif($price <= 0){
			$sender->sendMessage(TextFormat::RED . $this->translateString("sell.unlisted", ["$plot->X;$plot->Z"]));
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		// TODO: Implement getForm() method.
		return null;
	}
}
