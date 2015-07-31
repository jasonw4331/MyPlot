<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ClaimSubCommand extends SubCommand
{
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
        $plot = $this->getPlugin()->getPlotByPosition($player->getPosition());
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
        $plotLevel = $this->getPlugin()->getLevelSettings($plot->levelName);
        $maxPlotsInLevel = $plotLevel->maxPlotsPerPlayer;
        $maxPlots = $this->getPlugin()->getConfig()->get("MaxPlotsPerPlayer");
        $plotsOfPlayer = $this->getPlugin()->getProvider()->getPlotsByOwner($player->getName());
        if ($maxPlotsInLevel >= 0 and count($plotsOfPlayer) >= $maxPlotsInLevel) {
            $sender->sendMessage(TextFormat::RED . "You reached the limit of $maxPlotsInLevel plots per player in this world");
            return true;
        } elseif ($maxPlots >= 0 and count($plotsOfPlayer) >= $maxPlots) {
            $sender->sendMessage(TextFormat::RED . "You reached the limit of $maxPlots plots per player");
            return true;
        }

        $economy = $this->getPlugin()->getEconomyProvider();
        if ($economy !== null and !$economy->reduceMoney($player, $plotLevel->claimPrice)) {
            $sender->sendMessage(TextFormat::RED . "You don't have enough money to claim this plot");
            return true;
        }

        $plot->owner = $sender->getName();
        $plot->name = $name;
        if ($this->getPlugin()->getProvider()->savePlot($plot)) {
            $sender->sendMessage(TextFormat::GREEN . "You are now the owner of " . TextFormat::WHITE . $plot);
        } else {
            $sender->sendMessage(TextFormat::RED . "Something went wrong");
        }
        return true;
    }
}