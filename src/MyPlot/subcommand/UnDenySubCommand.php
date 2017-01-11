<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class UnDenySubCommand extends SubCommand
{
    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.undenyplayer");
    }

    public function execute(CommandSender $sender, array $args) {
        if ($sender instanceof Player);
        if (count($args) !== 1) {
            return false;
        }
        $dplayer = $args[0];
        $dp = $this->getPlugin()->getServer()->getPlayer($dplayer);
        $plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
        if ($plot === null) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
            return true;
        }
        if ($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.undenyplayer")) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
            return true;
        }
        if (!$plot->unDenyPlayer($dplayer)) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("undenyplayer.notdenied", [$dplayer]));
            return true;
        }
        if ($this->getPlugin()->savePlot($plot)) {
            $sender->sendMessage($this->translateString("undenyplayer.success1", [$dplayer]));
            if($dp  instanceof Player)
                $dp->sendMessage($this->translateString("undenyplayer.success2", [$plot->X,$plot->Z,$sender->getName()]));
        } else {
            $sender->sendMessage(TextFormat::RED . $this->translateString("error"));
        }
        return true;
    }
}