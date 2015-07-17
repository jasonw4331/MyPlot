<?php
namespace MyPlot;

use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use MyPlot\subcommand\SubCommand;
use MyPlot\subcommand\Claim;
use MyPlot\subcommand\Generate;

class Commands extends PluginCommand
{
    private $subCommands = [];

    /* @var SubCommand[] */
    private $commandObjects = [];

    public function __construct(MyPlot $plugin) {
        parent::__construct("plot", $plugin);
        $this->setAliases(["p"]);
        $this->setPermission("myplot.command");
        $this->setDescription("MyPlot commands");

        $this->loadSubCommand(new Claim($plugin));
        $this->loadSubCommand(new Generate($plugin));
    }

    private function loadSubCommand(Subcommand $command) {
        $this->commandObjects[] = $command;
        $commandId = count($this->commandObjects) - 1;
        $this->subCommands[$command->getName()] = $commandId;
        foreach ($command->getAliases() as $alias) {
            $this->subCommands[$alias] = $commandId;
        }
    }

    public function execute(CommandSender $sender, $alias, array $args) {
        if (isset($args[0]) === false) {
            $this->sendHelp($sender);
            return true;
        }
        $subCommand = strtolower(array_shift($args));
        if (isset($this->subCommands[$subCommand]) === false) {
            $this->sendHelp($sender);
            return true;
        }
        $commandId = $this->subCommands[$subCommand];
        $command = $this->commandObjects[$commandId];
        if ($sender->hasPermission("myplot.command." . $command->getName()) and $command->canUse($sender)) {
            if ($command->execute($sender, $args) === false) {
                $sender->sendMessage("Usage: /p " . $command->getName() . " " . $command->getUsage());
            }
        } else {
            $sender->sendMessage(TextFormat::RED . "You don't have permission to use this command");
        }
        return true;
    }

    private function sendHelp(CommandSender $sender) {
        $sender->sendMessage("===========[MyPlot commands]===========");
        foreach ($this->commandObjects as $command) {
            $sender->sendMessage(
                TextFormat::DARK_GREEN . "/p " . $command->getName() . " " . $command->getUsage() . ": " .
                TextFormat::WHITE . $command->getDescription()
            );
        }
    }
}