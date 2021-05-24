<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\HomeForm;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;
use function is_numeric;
use function usort;

class HomeSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.home");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
        if(empty($args)) {
            $plotNumber = 1;
        }elseif(is_numeric($args[0])) {
            $plotNumber = (int) $args[0];
        }else{
            $homePlayer = $args[0];
            if(($player = Server::getInstance()->getPlayer($homePlayer)) !== null)
                $homePlayer = $player->getName();
            if(!isset($args[1]) || !is_numeric($args[1]))
                $plotNumber = 1;
            else
                $plotNumber = (int)$args[1];
            $plots = $this->getPlugin()->getPlotsOfPlayer($homePlayer, $sender->getLevel()->getFolderName());
            if(empty($plots)) {
                $sender->sendMessage(MyPlot::PREFIX . C::RED . "Dieser Spieler hat noch keine Grundstücke!");
                return true;
            }
            if(!isset($plots[$plotNumber - 1])) {
                $sender->sendMessage(MyPlot::PREFIX . C::RED . "Der Spieler hat kein Grundstück mit dieser ID!");
                return true;
            }
            usort($plots, function(Plot $plot1, Plot $plot2) {
                if($plot1->levelName == $plot2->levelName) {
                    return 0;
                }
                return ($plot1->levelName < $plot2->levelName) ? -1 : 1;
            });
            /** @var Plot $plot */
            $plot = $plots[$plotNumber - 1];
            if($this->getPlugin()->teleportPlayerToPlot($sender, $plot)) {
                $sender->sendMessage(MyPlot::PREFIX.C::GREEN."Du wurdest zum Grundstück von ".C::YELLOW.$homePlayer.C::GREEN." teleportiert");
            }else{
                $sender->sendMessage(MyPlot::PREFIX . C::RED . $this->translateString("home.error"));
            }
            return true;
        }
        $levelName = $args[1] ?? $sender->getLevel()->getFolderName();
        $plots = $this->getPlugin()->getPlotsOfPlayer($sender->getName(), $levelName);
        if(empty($plots)) {
            $sender->sendMessage(MyPlot::PREFIX . C::RED . "Du hast noch keine Grundstücke");
            return true;
        }
        if(!isset($plots[$plotNumber - 1])) {
            $sender->sendMessage(MyPlot::PREFIX . C::RED . "Du hast kein Grundstück mit der ID");
            return true;
        }
        usort($plots, function(Plot $plot1, Plot $plot2) {
            if($plot1->levelName == $plot2->levelName) {
                return 0;
            }
            return ($plot1->levelName < $plot2->levelName) ? -1 : 1;
        });
        /** @var Plot $plot */
        $plot = $plots[$plotNumber - 1];
        if($this->getPlugin()->teleportPlayerToPlot($sender, $plot)) {
            $sender->sendMessage(MyPlot::PREFIX.C::GREEN."Du wurdest zu deinem Grundstück teleportiert!");
        }else{
            $sender->sendMessage(MyPlot::PREFIX . C::RED . $this->translateString("home.error"));
        }
        return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($player !== null and count($this->getPlugin()->getPlotsOfPlayer($player->getName(), $player->getLevelNonNull()->getFolderName())) > 0)
			return new HomeForm($player);
		return null;
	}
}