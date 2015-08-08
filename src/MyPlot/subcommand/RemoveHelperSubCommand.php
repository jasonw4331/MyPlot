<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class RemoveHelperSubCommand extends SubCommand
{
    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.removehelper");
    }

    public function getUsage() {
        return "<player>";
    }

    public function getName() {
        return "removehelper";
    }

    public function getDescription() {
        return "Remove a helper from your plot";
    }

    public function getAliases() {
        return ["delh"];
    }

    public function execute(CommandSender $sender, array $args) {
        if (count($args) !== 1) {
            return false;
        }
        $helper = $args[0];
        $player = $sender->getServer()->getPlayer($sender->getName());
        $plot = $this->getPlugin()->getPlotByPosition($player->getPosition());
        if ($plot === null) {
            $sender->sendMessage(TextFormat::RED . "You are not standing inside a plot");
            return true;
        }
        if ($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.removehelper")) {
            $sender->sendMessage(TextFormat::RED . "You are not the owner of this plot");
            return true;
        }
        if (!$plot->removeHelper($helper)) {
            $sender->sendMessage($helper . " was never a helper of this plot.");
            return true;
        }
        if ($this->getPlugin()->getProvider()->savePlot($plot)) {
            $sender->sendMessage(TextFormat::GREEN . $helper . " has been removed.");
        } else {
            $sender->sendMessage(TextFormat::RED . "Could not remove that player.");
        }
        return true;
    }
}
