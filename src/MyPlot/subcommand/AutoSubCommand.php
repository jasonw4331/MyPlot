<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class AutoSubCommand extends SubCommand
{
    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.auto");
    }

    public function getUsage() {
        return "";
    }

    public function getName() {
        return "auto";
    }

    public function getDescription() {
        return "Teleport to the next free plot";
    }

    public function getAliases() {
        return [];
    }

    public function execute(CommandSender $sender, array $args) {
        if (!empty($args)) {
            return false;
        }
        $player = $sender->getServer()->getPlayer($sender->getName());
        $levelName = $player->getLevel()->getName();
        if (!$this->getPlugin()->isLevelLoaded($levelName)) {
            $sender->sendMessage(TextFormat::RED . "You are not inside a plot world");
            return true;
        }
        if (($plot = $this->getPlugin()->getProvider()->getNextFreePlot($levelName)) !== null) {
            $this->getPlugin()->teleportPlayerToPlot($player, $plot);
            $sender->sendMessage(TextFormat::GREEN . "Teleported to " . TextFormat::WHITE . $plot);
        } else {
            $sender->sendMessage(TextFormat::RED . "No free plots found in this world");
        }
        return true;
    }
}