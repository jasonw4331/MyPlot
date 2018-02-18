<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class WarpSubCommand extends SubCommand
{
	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
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
		if(empty($args)) {
			return false;
		}
		$levelName = $args[1] ?? $sender->getLevel()->getFolderName();
		if(!$this->getPlugin()->isLevelLoaded($levelName)) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("warp.notinplotworld"));
			return true;
		}
		/** @var string[] $plotIdArray */
		$plotIdArray = explode(";", $args[0]);
		if(count($plotIdArray) != 2 or !is_numeric($plotIdArray[0]) or !is_numeric($plotIdArray[1])) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("warp.wrongid"));
			return true;
		}
		$plot = $this->getPlugin()->getProvider()->getPlot($levelName, (int) $plotIdArray[0], (int) $plotIdArray[1]);
		if($plot->owner == "" and !$sender->hasPermission("myplot.admin.warp")) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("warp.unclaimed"));
			return true;
		}
		$this->getPlugin()->teleportPlayerToPlot($sender, $plot);
		$plot = TextFormat::GREEN . $plot . TextFormat::WHITE;
		$sender->sendMessage($this->translateString("warp.success", [$plot]));
		return true;
	}
}