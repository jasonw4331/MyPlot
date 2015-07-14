<?php
namespace MyPlot;

use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\command\PluginCommand;
use pocketmine\Player;

class Commands extends PluginCommand
{
    private $commands = array(
        "generate",
        "help",
    );

    public function __construct(MyPlot $plugin){
        parent::__construct("p", $plugin);

        $this->setPermission("myplot.command");
        $this->setDescription("MyPlot commands");
    }

    public function execute(CommandSender $sender, $alias, array $args) {
        if (!$sender instanceof Player) {
            $sender->sendMessage("Please run this command in-game.");
            return true;
        }

        if (isset($args[0]) === false) {
            $this->commandHelp($sender, $args);
            return true;
        }

        $subCommand = strtolower($args[0]);
        if (in_array($subCommand, $this->commands) === false) {
            $this->commandHelp($sender, $args);
            return true;
        }

        array_shift($args);
        $this->{"command" . ucfirst($subCommand)}($sender, $args);
        return true;
    }

    public function commandGenerate(CommandSender $sender, array $args) {
        if (Server::getInstance()->isOp($sender->getName()) === false) {
            $sender->sendMessage("Only OP's can use this command");
            return;
        }

        if (count($args) !== 1) {
            $sender->sendMessage("Usage: /p generate [name]");
            return;
        }

        $levelName = $args[0];
        if (Server::getInstance()->getLevelByName($levelName) !== null) {
            $sender->sendMessage("A world with that name already exists");
            return;
        }

        $settings = $this->getPlugin()->getConfig()->get("default_generator");
        if (Server::getInstance()->generateLevel($levelName, null, MyPlotGenerator::class, $settings) === false) {
            $sender->sendMessage("Something went wrong");
            return;
        }

        /*
         * Teleport the player to the newly generated world
         *
        Server::getInstance()->loadLevel($levelName);
        $player = Server::getInstance()->getPlayer($sender->getName());
        $spawn = Server::getInstance()->getLevelByName($levelName)->getSpawnLocation();
        $player->teleport($spawn);
        */

        $sender->sendMessage("Successfully generated a new plot world: " . $levelName);
    }

    public function commandHelp(CommandSender $sender, array $args){
        $sender->sendMessage("===========[MyPlot commands]===========");
        $sender->sendMessage("/p generate [name] - Generate a new plot world");
        $sender->sendMessage("/p help - Show all available commands");
    }
}