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

    public function getUsage() {
        return "[plot number]";
    }

    public function getName() {
        return "home";
    }

    public function getDescription() {
        return "Teleport to your plot home. Use plot number if multiple homes";
    }

    public function getAliases() {
        return ["h"];
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
            $sender->sendMessage(TextFormat::RED . "You don't have any plots");
            return true;
        }
        if (!isset($plots[$plotNumber - 1])) {
            $sender->sendMessage(TextFormat::RED . "You don't have a plot with home number $plotNumber");
            return true;
        }
        $player = $this->getPlugin()->getServer()->getPlayer($sender->getName());
        $plot = $plots[$plotNumber - 1];
        if ($this->getPlugin()->teleportPlayerToPlot($player, $plot)) {
            $sender->sendMessage(TextFormat::GREEN . "Teleported to " . TextFormat::WHITE . $plot);
        } else {
            $sender->sendMessage(TextFormat::GREEN . "Could not teleport because plot world " . $plot->levelName . " is not loaded");
        }
        return true;
    }
}
