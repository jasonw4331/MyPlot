<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\InfoForm;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;
use function implode;

class InfoSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.info");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(isset($args[0])) {
			if(isset($args[1]) and is_numeric($args[1])) {
				$key = ((int) $args[1] - 1) < 1 ? 1 : ((int) $args[1] - 1);
				/** @var Plot[] $plots */
				$plots = [];
				foreach($this->getPlugin()->getPlotLevels() as $levelName => $settings) {
					$plots = array_merge($plots, $this->getPlugin()->getPlotsOfPlayer($args[0], $levelName));
				}
				if(isset($plots[$key])) {
					$plot = $plots[$key];
                    $sender->sendMessage(MyPlot::PREFIX."Grundstück-Informationen zum Grundstück ".C::YELLOW.$plot);
                    $sender->sendMessage(C::GOLD." Besitzer".C::DARK_GRAY.": ".C::GRAY.$plot->owner);
                    $sender->sendMessage(C::GOLD." PvP".C::DARK_GRAY.": ".($plot->pvp ? C::GREEN."Aktiviert" : C::RED."Deaktiviert"));
                    $sender->sendMessage(C::GOLD." Helfer".C::DARK_GRAY.": ".(empty($plot->helpers) ? C::RED."Keine" : C::YELLOW.implode(C::GRAY.", ".C::YELLOW, $plot->helpers)));
                    $sender->sendMessage(C::GOLD." Verboten".C::DARK_GRAY.": ".(empty($plot->denied) ? C::RED."Keine" : C::YELLOW.implode(C::GRAY.", ".C::YELLOW, $plot->denied)));
                }else{
					$sender->sendMessage(C::RED . $this->translateString("info.notfound"));
				}
			}else{
				return false;
			}
		}else{
			$plot = $this->getPlugin()->getPlotByPosition($sender);
			if($plot === null) {
				$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du stehst auf keinem Grundstück!");
				return true;
			}
            if($plot->owner === "") {
                $sender->sendMessage(MyPlot::PREFIX . C::RED . "Das Grundstück hat noch keinen Besitzer!");
                return true;
            }
            $sender->sendMessage(MyPlot::PREFIX."Grundstück-Informationen zum Grundstück ".C::YELLOW.$plot);
            $sender->sendMessage(C::GOLD." Besitzer".C::DARK_GRAY.": ".C::GRAY.$plot->owner);
            $sender->sendMessage(C::GOLD." PvP".C::DARK_GRAY.": ".($plot->pvp ? C::GREEN."Aktiviert" : C::RED."Deaktiviert"));
            $sender->sendMessage(C::GOLD." Helfer".C::DARK_GRAY.": ".(empty($plot->helpers) ? C::RED."Keine" : C::YELLOW.implode(C::GRAY.", ".C::YELLOW, $plot->helpers)));
            $sender->sendMessage(C::GOLD." Verboten".C::DARK_GRAY.": ".(empty($plot->denied) ? C::RED."Keine" : C::YELLOW.implode(C::GRAY.", ".C::YELLOW, $plot->denied)));
        }
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($player !== null and $this->getPlugin()->getPlotByPosition($player) instanceof Plot)
			return new InfoForm($player);
		return null;
	}
}