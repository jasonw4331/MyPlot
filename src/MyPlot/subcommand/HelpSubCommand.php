<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class HelpSubCommand extends SubCommand
{
    public function canUse(CommandSender $sender) {
        return $sender->hasPermission("myplot.command.help");
    }

    /**
     * @return \MyPlot\Commands
     */
    private function getCommandHandler()
    {
        return $this->getPlugin()->getCommand($this->translateString("command.name"));
    }

    public function execute(CommandSender $sender, array $args) {
        $sender->sendMessage($this->translateString("help.header"));
        foreach ($this->getCommandHandler()->getCommands() as $command) {
            if ($command->canUse($sender)) {
                $sender->sendMessage(TextFormat::DARK_GREEN . $command->getName() . ": " . TextFormat::WHITE . $command->getDescription());
            }
        }
        return true;
    }
}
