<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class HomesSubCommand extends SubCommand
{
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
		$levelName = $args[0] ?? $sender->getWorld()->getFolderName();
		if(!$this->plugin->isLevelLoaded($levelName)) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("error", [$levelName]));
			return true;
		}
		$plots = $this->plugin->getPlotsOfPlayer($sender->getName(), $levelName);
		if(count($plots) === 0) {
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

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return null; // we can just list homes in the home form
	}
}