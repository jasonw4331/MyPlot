<?php
namespace MyPlot\subcommand;

use MyPlot\MyPlot;
use MyPlot\Commands;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class HelpSubCommand extends SubCommand {

    /** @var Commands $cmds */
    private $cmds = null;

    /**
     * @param MyPlot $plugin
     * @param string $name
     * @param Commands $commands
     */
    public function __construct(MyPlot $plugin, $name, Commands $commands) {
        parent::__construct($plugin, $name);
        $this->cmds = $commands;
    }

    /**
     * @param CommandSender $sender
     * @return bool
     */
    public function canUse(CommandSender $sender) {
        return $sender->hasPermission("myplot.command.help");
    }

    public function execute(CommandSender $sender, array $args) {
        $sender->sendMessage(TextFormat::YELLOW."=======+--MyPlot--+=======");
        foreach($this->cmds->getCommands() as $cmd) {
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