<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\WarpForm;
use MyPlot\MyPlot;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;

class WarpSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.warp");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(count($args) === 0) {
            $sender->sendMessage(C::RED."/p warp <PlotID>");
            return true;
		}
		$levelName = $args[1] ?? $sender->getLevelNonNull()->getFolderName();
		if(!$this->getPlugin()->isLevelLoaded($levelName)) {
            $sender->sendMessage(MyPlot::PREFIX . C::RED . "Du bist nicht in der Grundstückwelt!");
			return true;
		}
		/** @var string[] $plotIdArray */
		$plotIdArray = explode(";", $args[0]);
		if(count($plotIdArray) != 2 or !is_numeric($plotIdArray[0]) or !is_numeric($plotIdArray[1])) {
            $sender->sendMessage(MyPlot::PREFIX . C::RED . "Die ID ist ungültig!");
			return true;
		}
		$plot = $this->getPlugin()->getProvider()->getPlot($levelName, (int) $plotIdArray[0], (int) $plotIdArray[1]);
		if($plot->owner == "" and !$sender->hasPermission("myplot.admin.warp")) {
            $sender->sendMessage(MyPlot::PREFIX . C::RED . "Dieses Grundstück gehört niemandem.");
			return true;
		}
		if($this->getPlugin()->teleportPlayerToPlot($sender, $plot)) {
            $sender->sendMessage(MyPlot::PREFIX.C::GREEN."Du wurdest zum Grundstück ".C::YELLOW.$plot.C::GREEN." teleportiert");
		}else{
			$sender->sendMessage(C::RED . $this->translateString("generate.error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return $player !== null ? new WarpForm($player) : null;
	}
}