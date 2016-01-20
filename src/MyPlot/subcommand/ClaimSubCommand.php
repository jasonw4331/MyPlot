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
            $sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
            return true;
        }
        if ($plot->owner != "") {
            if ($plot->owner === $sender->getName()) {
                $sender->sendMessage(TextFormat::RED . $this->translateString("claim.yourplot"));
            } else {
                $sender->sendMessage(TextFormat::RED . $this->translateString("claim.alreadyclaimed", [$plot->owner]));
            }
            return true;
        }
        $plotLevel = $this->getPlugin()->getLevelSettings($plot->levelName);
        $maxPlotsInLevel = $plotLevel->maxPlotsPerPlayer;
        $maxPlots = $this->getPlugin()->getConfig()->get("MaxPlotsPerPlayer");
        $plotsOfPlayer = $this->getPlugin()->getProvider()->getPlotsByOwner($player->getName());
        if ($maxPlots >= 0 and count($plotsOfPlayer) >= $maxPlots) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("claim.maxperplayer", [$maxPlots]));
            return true;
        } elseif ($maxPlotsInLevel >= 0 and count($plotsOfPlayer) >= $maxPlotsInLevel) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("claim.maxperworld", [$maxPlotsInLevel]));
            return true;
        }

        $economy = $this->getPlugin()->getEconomyProvider();
        if ($economy !== null and !$economy->reduceMoney($player, $plotLevel->claimPrice)) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("claim.nomoney"));
            return true;
        }

        $plot->owner = $sender->getName();
        $plot->name = $name;
        if ($this->getPlugin()->getProvider()->savePlot($plot)) {
            $sender->sendMessage($this->translateString("claim.success"));
        } else {
            $sender->sendMessage(TextFormat::RED . $this->translateString("error"));
        }
        return true;
    }
}