<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class GiveSubCommand extends SubCommand
{
    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.give");
    }

    public function execute(CommandSender $sender, array $args) {
        if (count($args) > 2) {
            return false;
        }
        
        $player = $sender->getServer()->getPlayer($sender->getName());
        $plot = $this->getPlugin()->getPlotByPosition($player->getPosition());
        if ($plot === null) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
            return true;
        }
        if ($plot->owner !== $sender->getName()) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
            return true;
        }
        if ($args[1] !instanceof Player) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("give.notonline"));
        }
        
        $maxPlots = $this->getPlugin()->getConfig()->get("MaxPlotsPerPlayer");
        $plotsOfPlayer = $this->getPlugin()->getProvider()->getPlotsByOwner($args[1]);
        if ($maxPlots >= 0 and count($plotsOfPlayer) >= $maxPlots) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("give.maxedout", [$maxPlots]));
            return true;
        }

        $plot->owner = $args[1];
        if ($this->getPlugin()->getProvider()->savePlot($plot)) {
            $sender->sendMessage($this->translateString("give.success"));
        } else {
            $sender->sendMessage(TextFormat::RED . $this->translateString("error"));
        }
        return true;
    }
}
