<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\MyPlot;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;

class ClearSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.clear");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		$plot = $this->getPlugin()->getPlotByPosition($sender);
		if($plot === null) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du stehst auf keinem Grundstück!");
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.clear")) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du bist nicht Besitzer dieses Grundstücks!");
			return true;
		}
		if(isset($args[0]) and $args[0] == $this->translateString("confirm")) {
			$economy = $this->getPlugin()->getEconomyProvider();
			$price = $this->getPlugin()->getLevelSettings($plot->levelName)->clearPrice;
			if($economy !== null and !$economy->reduceMoney($sender, $price)) {
				$sender->sendMessage(C::RED . $this->translateString("clear.nomoney"));
				return true;
			}
			$maxBlocksPerTick = $this->getPlugin()->getConfig()->get("ClearBlocksPerTick", 256);
			if(!is_int($maxBlocksPerTick))
				$maxBlocksPerTick = 256;
			if($this->getPlugin()->clearPlot($plot, $maxBlocksPerTick)) {
                $sender->sendMessage(MyPlot::PREFIX . C::GREEN."Das Grundstück wird nun geleert.");
			}else{
				$sender->sendMessage(C::RED . $this->translateString("error"));
			}
		}else{
            $sender->sendMessage(MyPlot::PREFIX.C::RED."Bitte gebe ".C::YELLOW."/plot clear confirm".C::RED." ein, um zu bestätigen, dass dein GESAMTES GRUNDSTÜCK GELEERT wird.");
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return null;
	}
}