<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class DisposeSubCommand extends SubCommand
{
    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.dispose");
    }

    public function getUsage() {
        return "";
    }

    public function getName() {
        return "dispose";
    }

    public function getDescription() {
        return "Disposes the plot you're standing on";
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
        if ($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.dispose")) {
            $sender->sendMessage(TextFormat::RED . "You are not the owner of this plot");
            return true;
        }

        $economy = $this->getPlugin()->getEconomyProvider();
        $price = $this->getPlugin()->getLevelSettings($plot->levelName)->disposePrice;
        if ($economy !== null and !$economy->reduceMoney($player, $price)) {
            $sender->sendMessage(TextFormat::RED . "You don't have enough money to dispose this plot");
            return true;
        }

        if ($this->getPlugin()->disposePlot($plot)) {
            $sender->sendMessage(TextFormat::GREEN . "Plot disposed");
        } else {
            $sender->sendMessage(TextFormat::RED . "Could not dispose this plot");
        }
        return true;
    }
}
