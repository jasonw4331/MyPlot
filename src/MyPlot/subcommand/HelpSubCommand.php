<?php
namespace MyPlot\subcommand;

use MyPlot\MyPlot;
use MyPlot\Commands;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\utils\TextFormat;

class HelpSubCommand extends SubCommand {
    public function canUse(CommandSender $sender) {
        return $sender->hasPermission("myplot.command.help");
    }

    public function execute(CommandSender $sender, array $args) {
        $sender->sendMessage(TextFormat::YELLOW."=======+--MyPlot--+=======");
        foreach(Commands::getCommands() as $cmd) {
            $usage = MyPlot::getInstance()->getLanguage()->translateString("subcommand.usage", [$cmd->getUsage()]);
            if($sender->isOp()) {
                if(!$cmd->getPermisson()->getDefault() == "false") {
                    $sender->sendMessage(TextFormat::YELLOW.$usage);
                }
            }else{
                if(!$cmd->getPermisson()->getDefault() == "op" and !$cmd->getPermisson()->getDefault() == "false") {
                    $sender->sendMessage(TextFormat::YELLOW.$usage);
                }
            }
        }
        $sender->sendMessage(TextFormat::YELLOW."==========================");
        return true;
    }
}
