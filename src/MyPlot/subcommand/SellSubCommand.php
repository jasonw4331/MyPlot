<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use function count;
use function explode;
use function is_numeric;

class SellSubCommand extends SubCommand
{
	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
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
		if($this->getPlugin()->getEconomyProvider() === null){
			$sender->sendMessage(TextFormat::RED . "noeconomy");
			return true;
		}
		if(empty($args))
			return false;
		$plot = $this->getPlugin()->getPlotByPosition($sender->asPosition());
		if($plot === null){
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if($plot->name !== $sender->getName() and !$sender->hasPermission("myplot.admin.sell")){
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		if(!is_numeric($args[0]))
			return false;
		$price = (float)$args[0];
		if($price <= 0){
			$sender->sendMessage(TextFormat::RED . $this->translateString("sell.unlist"));
		}
		if($this->getPlugin()->sellPlot($plot, $price)) {
			$sender->sendMessage($this->translateString("sell.success", [$price]));
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