<?php
namespace MyPlot\subcommand;

use MyPlot\MyPlot;
use MyPlot\Commands;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class HelpSubCommand extends SubCommand {
    public function canUse(CommandSender $sender) {
        return $sender->hasPermission("myplot.command.help");
    }

    public function execute(CommandSender $sender, array $args) {
        $sender->sendMessage(TextFormat::YELLOW."=======+--MyPlot--+=======");
        foreach(Commands::getCmd() as $cmd) {
            $usage = MyPlot::getInstance()->getLanguage()->translateString("subcommand.usage", [$cmd->getUsage()]);
            if($sender->isOp()) {
                $sender->sendMessage(TextFormat::YELLOW.$usage);
            }else{
                $sender->sendMessage(TextFormat::YELLOW.$usage);
            }
        }
        $sender->sendMessage(TextFormat::YELLOW."==========================");
        return true;
    }
}