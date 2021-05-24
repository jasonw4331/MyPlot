<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\MyPlot;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;

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
		$levelName = $args[0] ?? $sender->getLevelNonNull()->getFolderName();
		$plots = $this->getPlugin()->getPlotsOfPlayer($sender->getName(), $levelName);
		if(count($plots) === 0) {
            $sender->sendMessage(MyPlot::PREFIX . C::RED . "Du hast noch keine Grundstücke!");
			return true;
		}
        $sender->sendMessage(MyPlot::PREFIX.C::GOLD."Deine Grundstücke");
		for($i = 0; $i < count($plots); $i++) {
			$plot = $plots[$i];
			$message = C::DARK_GREEN . ($i + 1) . ") ";
			$message .= C::WHITE . $plot->levelName . " " . $plot;
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