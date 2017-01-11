<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class SetOwnerSubCommand extends SubCommand {
    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.admin.setowner");
    }
    public function execute(CommandSender $sender, array $args) {
        if (count($args) < 1) {
            return false;
        }
        $player = $sender->getServer()->getPlayer($sender->getName());
        $plot = $this->getPlugin()->getPlotByPosition($player->getPosition());
        if ($plot === null) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
            return true;
        }
        $maxPlots = $this->getPlugin()->getMaxPlotsOfPlayer($player);
        $plotsOfPlayer = count($this->getPlugin()->getPlotsOfPlayer($player->getName(),$player->getLevel()));
        if ($plotsOfPlayer >= $maxPlots) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("setowner.maxplots", [$maxPlots]));
            return true;
        }
        $plot->owner = $args[0];
        $plot->name = "";
        if ($this->getPlugin()->savePlot($plot)) {
            $sender->sendMessage($this->translateString("setowner.success", [$plot->owner]));
        } else {
            $sender->sendMessage(TextFormat::RED . $this->translateString("error"));
        }
        return true;
    }
}