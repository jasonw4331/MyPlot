<?php
namespace MyPlot;

use MyPlot\subcommand\AddHelperSubCommand;
use MyPlot\subcommand\ClaimSubCommand;
use MyPlot\subcommand\GenerateSubCommand;
use MyPlot\subcommand\InfoSubCommand;
use MyPlot\subcommand\ListSubCommand;
use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use MyPlot\subcommand\SubCommand;
use pocketmine\Player;

class Commands extends PluginCommand
{
    private $subCommands = [];

    /* @var SubCommand[] */
    private $commandObjects = [];

    public function __construct(MyPlot $plugin) {
        parent::__construct("plot", $plugin);
        $this->setAliases(["p"]);
        $this->setPermission("myplot.command");
        $this->setDescription("Claim and manage your plots");

        $this->loadSubCommand(new ClaimSubCommand($plugin));
        $this->loadSubCommand(new GenerateSubCommand($plugin));
        $this->loadSubCommand(new ListSubCommand($plugin));
        $this->loadSubCommand(new InfoSubCommand($plugin));
        $this->loadSubCommand(new AddHelperSubCommand($plugin));
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
        $canUse = $command->canUse($sender);
        if ($sender->hasPermission("myplot.command." . $command->getName()) and $canUse) {
            if ($command->execute($sender, $args) === false) {
                $sender->sendMessage("Usage: /p " . $command->getName() . " " . $command->getUsage());
            }
        } elseif ($canUse === false and !($sender instanceof Player)) {
            $sender->sendMessage(TextFormat::RED . "Please run this command in-game.");
        } else {
            $sender->sendMessage(TextFormat::RED . "You do not have permissions to run this command");
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