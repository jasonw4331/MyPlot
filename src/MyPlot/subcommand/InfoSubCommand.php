<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class InfoSubCommand extends SubCommand
{
    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.info");
    }

    public function getUsage() {
        return "";
    }

    public function getName() {
        return "info";
    }

    public function getDescription() {
        return "Get info about the plot you are standing on";
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
        $sender->sendMessage(TextFormat::DARK_GREEN . "Info about " . TextFormat::WHITE . $plot);
        $sender->sendMessage(TextFormat::DARK_GREEN. "Name: " . TextFormat::WHITE . $plot->name);
        $sender->sendMessage(TextFormat::DARK_GREEN. "Owner: " . TextFormat::WHITE . $plot->owner);
        $helpers = implode(", ", $plot->helpers);
        $sender->sendMessage(TextFormat::DARK_GREEN. "Helpers: " . TextFormat::WHITE . $helpers);
        $sender->sendMessage(TextFormat::DARK_GREEN. "Biome: " . TextFormat::WHITE . $plot->biome);
        return true;
    }
}