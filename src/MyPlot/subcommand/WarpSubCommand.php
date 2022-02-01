<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\WarpForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

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
			return false;
		}
		$levelName = $args[1] ?? $sender->getWorld()->getFolderName();
		if(!$this->plugin->isLevelLoaded($levelName)) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("warp.notinplotworld"));
			return true;
		}
		$plotIdArray = explode(";", $args[0]);
		if(count($plotIdArray) != 2 or !is_numeric($plotIdArray[0]) or !is_numeric($plotIdArray[1])) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("warp.wrongid"));
			return true;
		}
		$plot = $this->plugin->getProvider()->getPlot($levelName, (int) $plotIdArray[0], (int) $plotIdArray[1]);
		if($plot->owner == "" and !$sender->hasPermission("myplot.admin.warp")) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("warp.unclaimed"));
			return true;
		}
		if($this->plugin->teleportPlayerToPlot($sender, $plot)) {
			$plot = TextFormat::GREEN . $plot . TextFormat::WHITE;
			$sender->sendMessage($this->translateString("warp.success", [$plot]));
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("generate.error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return $player !== null ? new WarpForm($player) : null;
	}
}