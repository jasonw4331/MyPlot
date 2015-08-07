<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class AdminSubCommand extends SubCommand
{
    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.admin");
    }

    public function getUsage() {
        return "<parameters>";
    }

    public function getName() {
        return "admin";
    }

    public function getDescription() {
        return "Administer plots";
    }

    public function getAliases() {
        return [];
    }

    public function execute(CommandSender $sender, array $args) {
       if (count($args) !== 1) {
            return false;
        }
        $player = $sender->getServer()->getPlayer($sender->getName());
        $plot = $this->getPlugin()->getPlotByPosition($player->getPosition());
        if ($plot === null) {
            $sender->sendMessage(TextFormat::RED . "You are not standing inside a plot");
            return true;
        }

        if ($args[0] === "reset") {
          if ($this->getPlugin()->resetPlot($plot)) {
              $sender->sendMessage(TextFormat::GREEN . "Plot reset");
          } else {
              $sender->sendMessage(TextFormat::RED . "Could not reset this plot");
          }
        }
        if ($args[0] === "help") {
         $sender->sendMessage(TextFormat::YELLOW . "===[MyPlot Admin]===");
         $sender->sendMessage(TextFormat::AQUA . "/p admin reset : ".TextFormat::WHITE."Reset your plot");
         $sender->sendMessage(TextFormat::AQUA . "/p admin help : ".TextFormat::WHITE."Pull up this help menu");
         }
        return true;
    }
}
