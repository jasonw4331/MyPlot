<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class GenerateSubCommand extends SubCommand
{
    public function canUse(CommandSender $sender) {
        return $sender->hasPermission("myplot.command.generate");
    }

    public function execute(CommandSender $sender, array $args) {
        if (count($args) !== 1) {
            return false;
        }
        $levelName = $args[0];
        if ($sender->getServer()->isLevelGenerated($levelName)) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("generate.exists", [$levelName]));
            return true;
        }
        if ($this->getPlugin()->generateLevel($levelName)) {
            $sender->sendMessage($this->translateString("generate.success", [$levelName]));
        } else {
            $sender->sendMessage(TextFormat::RED . $this->translateString("generate.error"));
        }
        return true;
    }
}