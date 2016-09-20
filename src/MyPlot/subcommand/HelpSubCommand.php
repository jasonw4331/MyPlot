<?php
namespace MyPlot\subcommand;

use MyPlot\MyPlot;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\utils\TextFormat;

class HelpSubCommand extends SubCommand
{
    public function canUse(CommandSender $sender) {
        return $sender->hasPermission("myplot.command.help");
    }

    public function execute(CommandSender $sender, array $args) {
        if($args[0]) {
            
        }
        $sender->sendMessage(TextFormat::$this->translateString("help.header", [$pageNumber, 5]));
        $sender->sendMessage(TextFormat::DARK_GREEN.": ".TextFormat::WHITE."");
        
        return true;
    }
}
