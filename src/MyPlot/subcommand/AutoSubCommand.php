<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class AutoSubCommand extends SubCommand
{
	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.auto");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		$levelName = $sender->getLevel()->getFolderName();
		if(!$this->getPlugin()->isLevelLoaded($levelName)) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("auto.notplotworld"));
			return true;
		}
		if(($plot = $this->getPlugin()->getNextFreePlot($levelName)) !== null) {
			$this->getPlugin()->teleportMiddle($sender, $plot);
			$sender->sendMessage($this->translateString("auto.success", [$plot->X, $plot->Z]));
			$cmd = new ClaimSubCommand($this->getPlugin(), "claim");
			if(isset($args[0]) and strtolower($args[0]) == "true" and $cmd->canUse($sender)) {
				$cmd->execute($sender, [$args[1]]);
			}
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("auto.noplots"));
		}
		return true;
	}
}