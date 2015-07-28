<?php
namespace MyPlot\subcommand;

use MyPlot\MyPlot;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ClaimSubCommand implements SubCommand
{
    private $plugin;

    public function __construct(MyPlot $plugin) {
        $this->plugin = $plugin;
    }

    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.claim");
    }

    public function getUsage() {
        return "[name]";
    }

    public function getName() {
        return "claim";
    }

    public function getDescription() {
        return "Claim the plot you're standing on";
    }

    public function getAliases() {
        return [];
    }

    public function execute(CommandSender $sender, array $args) {
        if (count($args) > 1) {
            return false;
        }
        $name = "";
        if (isset($args[0])) {
            $name = $args[0];
        }
        $player = $sender->getServer()->getPlayer($sender->getName());
        $plot = $this->plugin->getPlotByPosition($player->getPosition());
        if ($plot === null) {
            $sender->sendMessage(TextFormat::RED . "You are not standing inside a plot");
            return true;
        }
        if ($plot->owner != "") {
            if ($plot->owner === $sender->getName()) {
                $sender->sendMessage(TextFormat::RED . "You already own this plot");
            } else {
                $sender->sendMessage(TextFormat::RED . "This plot is already claimed by " . $plot->owner);
            }
            return true;
        }
        $maxPlotsInLevel = $this->plugin->getLevelSettings($plot->levelName)->maxPlotsPerPlayer;
        $maxPlots = $this->plugin->getConfig()->get("MaxPlotsPerPlayer");
        $plotsOfPlayer = $this->plugin->getProvider()->getPlotsByOwner($player->getName());
        if ($maxPlotsInLevel >= 0 and count($plotsOfPlayer) >= $maxPlotsInLevel) {
            $sender->sendMessage(TextFormat::RED . "You reached the limit of $maxPlotsInLevel plots per player in this world");
            return true;
        } elseif ($maxPlots >= 0 and count($plotsOfPlayer) >= $maxPlots) {
            $sender->sendMessage(TextFormat::RED . "You reached the limit of $maxPlots plots per player");
            return true;
        }

        $plot->owner = $sender->getName();
        $plot->name = $name;
        if ($this->plugin->getProvider()->savePlot($plot)) {
            $sender->sendMessage(TextFormat::GREEN . "You are now the owner of " . TextFormat::WHITE . $plot);
        } else {
            $sender->sendMessage(TextFormat::RED . "Something went wrong");
        }
        return true;
    }
}