<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class FindSubCommand extends SubCommand
{
	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.find");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(!isset($args[0])) {
			return false;
		}
		/** @var Plot[] $plots */
		$plots = [];
		foreach($this->getPlugin()->getPlotLevels() as $levelName => $levelSettings) {
			$plots = array_merge($plots, $this->getPlugin()->getPlotsOfPlayer($args[0], $levelName));
		}

		$sender->sendMessage(TextFormat::GREEN.$this->translateString("find.header"));
		foreach($plots as $plot) {
			$sender->sendMessage(TextFormat::BLUE.$this->translateString("find.line1", [$plot->name.TextFormat::WHITE, $plot->owner]));
			$sender->sendMessage(TextFormat::AQUA.$this->translateString("find.line1", [$plot->levelName, $plot->X.";".$plot->Z]));
		}
		return true;
	}
}