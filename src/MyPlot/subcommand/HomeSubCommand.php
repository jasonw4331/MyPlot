<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class HomeSubCommand extends SubCommand
{
    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.home");
    }

    public function execute(CommandSender $sender, array $args) {
        if (empty($args)) {
            $plotNumber = 1;
        } elseif (count($args) === 1 and is_numeric($args[0])) {
            $plotNumber = (int) $args[0];
        } else {
            return false;
        }
        $plots = $this->getPlugin()->getProvider()->getPlotsByOwner($sender->getName());
        if (empty($plots)) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("home.noplots"));
            return true;
        }
        if (!isset($plots[$plotNumber - 1])) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("home.notexist", [$plotNumber]));
            return true;
        }
        
        usort($plots, function ($plot1, $plot2) {
            /** @var $plot1 Plot */
            /** @var $plot2 Plot */
            if ($plot1->levelName == $plot2->levelName) {
                return 0;
            }
            return ($plot1->levelName < $plot2->levelName) ? -1 : 1;
        });
        
        $player = $this->getPlugin()->getServer()->getPlayer($sender->getName());
        $plot = $plots[$plotNumber - 1];
        if ($this->getPlugin()->teleportPlayerToPlot($player, $plot)) {
            $sender->sendMessage($this->translateString("home.success", [$plot]));
        } else {
            $sender->sendMessage(TextFormat::RED . $this->translateString("home.error"));
        }
        return true;
    }
}
