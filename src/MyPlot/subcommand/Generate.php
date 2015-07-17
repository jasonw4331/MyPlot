<?php
namespace MyPlot\subcommand;

use MyPlot\MyPlot;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Generate implements SubCommand
{
    private $plugin;

    public function __construct(MyPlot $plugin) {
        $this->plugin = $plugin;
    }

    public function canUse(CommandSender $sender) {
        return (($sender instanceof Player) === false) or $sender->isOp();
    }

    public function getUsage() {
        return "<name>";
    }

    public function getName() {
        return "generate";
    }

    public function getDescription() {
        return "Generate a new plot world";
    }

    public function getAliases() {
        return ["gen"];
    }

    public function execute(CommandSender $sender, array $args) {
        if (count($args) !== 1) {
            return false;
        }
        $levelName = $args[0];
        if ($sender->getServer()->isLevelGenerated($levelName)) {
            $sender->sendMessage(TextFormat::RED . "A level with name $levelName already exists");
            return true;
        }
        if ($this->plugin->generateLevel($levelName)) {
            $sender->sendMessage(TextFormat::GREEN . "Successfully generated plot world $levelName");
        } else {
            $sender->sendMessage(TextFormat::RED . "Something went wrong");
        }
        return true;
    }
}