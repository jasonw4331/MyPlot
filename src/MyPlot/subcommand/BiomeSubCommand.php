<?php
namespace MyPlot\subcommand;

use MyPlot\MyPlot;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class BiomeSubCommand implements SubCommand
{
    private $plugin;

    public function __construct(MyPlot $plugin) {
        $this->plugin = $plugin;
    }

    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.biome");
    }

    public function getUsage() {
        return "/p biome <biome>";
    }

    public function getName() {
        return "biome";
    }

    public function getDescription() {
        return "Changes your plot's biome";
    }

    public function getAliases() {
        return [];
    }

    public function execute(CommandSender $sender, array $args) {
        if (count($args) !==1) {
            return false;
        }
        $player = $sender->getServer()->getPlayer($sender->getName());
        $biome = $args[0];
        $plot = $this->plugin->getPlotByPosition($player->getPosition());
        if ($plot === null) {
            $sender->sendMessage(TextFormat::RED . "You are not standing inside a plot");
            return true;
        }
        if ($plot->owner !== $sender->getName()) {
            $sender->sendMessage(TextFormat::RED . "You are not the owner of this plot");
            return true;
        }
        if ($this->plugin->setPlotBiome($plot, $biome)) {
            $sender->sendMessage(TextFormat::GREEN . "Changed the plot biome");
        } else {
            $sender->sendMessage(TextFormat::RED . "Could not change the plot biome");
        }
        return true;
    }
}
