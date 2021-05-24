<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\MyPlot;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;

class ResetSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.reset");
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
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.reset")) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du bist nicht Besitzer dieses Grundstücks!");
			return true;
		}
		if(isset($args[0]) and $args[0] == $this->translateString("confirm")) {
			$economy = $this->getPlugin()->getEconomyProvider();
			$price = $this->getPlugin()->getLevelSettings($plot->levelName)->resetPrice;
			if($economy !== null and !$economy->reduceMoney($sender, $price)) {
				$sender->sendMessage(C::RED . $this->translateString("reset.nomoney"));
				return true;
			}
			/** @var int $maxBlocksPerTick */
			$maxBlocksPerTick = $this->getPlugin()->getConfig()->get("ClearBlocksPerTick", 256);
			if($this->getPlugin()->resetPlot($plot, $maxBlocksPerTick)) {
                $sender->sendMessage(MyPlot::PREFIX . C::RED."Das Grundstück wird nun zurückgesetzt.");
			}else{
				$sender->sendMessage(C::RED . $this->translateString("error"));
			}
		}else{
            $sender->sendMessage(MyPlot::PREFIX.C::RED."Bitte gebe ".C::YELLOW."/plot reset confirm".C::RED." ein, um zu bestätigen, dass dein GESAMTES GRUNDSTÜCK RESETTET wird.");
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return null;
	}
}