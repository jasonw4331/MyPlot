<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ResetSubCommand extends SubCommand
{
    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.reset");
    }

    public function getUsage() {
        return "";
    }

    public function getName() {
        return "reset";
    }

    public function getDescription() {
        return "Disposes and clears the plot you're standing on";
    }

    public function getAliases() {
        return [];
    }

    public function execute(CommandSender $sender, array $args) {
        if (!empty($args)) {
            return false;
        }
        $player = $sender->getServer()->getPlayer($sender->getName());
        $plot = $this->getPlugin()->getPlotByPosition($player->getPosition());
        if ($plot === null) {
            $sender->sendMessage(TextFormat::RED . "You are not standing inside a plot");
            return true;
        }
        if ($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.reset")) {
            $sender->sendMessage(TextFormat::RED . "You are not the owner of this plot");
            return true;
        }

        $economy = $this->getPlugin()->getEconomyProvider();
        $price = $this->getPlugin()->getLevelSettings($plot->levelName)->resetPrice;
        if ($economy !== null and !$economy->reduceMoney($player, $price)) {
            $sender->sendMessage(TextFormat::RED . "You don't have enough money to reset this plot");
            return true;
        }

        if ($this->getPlugin()->resetPlot($plot)) {
            $sender->sendMessage(TextFormat::GREEN . "Plot reset");
        } else {
            $sender->sendMessage(TextFormat::RED . "Could not reset this plot");
        }
        return true;
    }
}
