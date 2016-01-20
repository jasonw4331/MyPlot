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
        if (count($args) !== 1) {
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

        $newOwner = $this->getPlugin()->getServer()->getPlayer($args[0]);
        if (!($newOwner instanceof Player)) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("give.notonline"));
            return true;
        } elseif ($newOwner === $player) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("give.toself"));
            return true;
        }

        $maxPlotsGlobal = $this->getPlugin()->getConfig()->get("MaxPlotsPerPlayer");
        $maxPlotsInLevel = $this->getPlugin()->getLevelSettings($plot->levelName)->maxPlotsPerPlayer;
        $plotsGlobal = count($this->getPlugin()->getProvider()->getPlotsByOwner($newOwner->getName()));
        $plotsInLevel = count($this->getPlugin()->getProvider()->getPlotsByOwner($newOwner->getName(), $plot->levelName));
        if ($maxPlotsGlobal <= $plotsGlobal or $maxPlotsInLevel <= $plotsInLevel) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("give.maxedout"));
            return true;
        }

        $plot->owner = $newOwner->getName();
        if ($this->getPlugin()->getProvider()->savePlot($plot)) {
            $sender->sendMessage($this->translateString("give.success", [$newOwner->getName()]));
        } else {
            $sender->sendMessage(TextFormat::RED . $this->translateString("error"));
        }
        return true;
    }
}
