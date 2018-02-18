<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class HomesSubCommand extends SubCommand
{
	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.homes");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		$levelName = $args[0] ?? $sender->getLevel()->getFolderName();
		$plots = $this->getPlugin()->getPlotsOfPlayer($sender->getName(), $levelName);
		if(empty($plots)) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("homes.noplots"));
			return true;
		}
		$sender->sendMessage(TextFormat::DARK_GREEN . $this->translateString("homes.header"));
		for($i = 0; $i < count($plots); $i++) {
			$plot = $plots[$i];
			$message = TextFormat::DARK_GREEN . ($i + 1) . ") ";
			$message .= TextFormat::WHITE . $plot->levelName . " " . $plot;
			if($plot->name !== "") {
				$message .= " = " . $plot->name;
			}
			$sender->sendMessage($message);
		}
		return true;
	}
}