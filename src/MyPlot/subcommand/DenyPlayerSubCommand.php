<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class DenyPlayerSubCommand extends SubCommand
{
    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.denyplayer");
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
        if ($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.denyplayer")) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
            return true;
        }
        if($this->getPlugin()->getServer()->getPlayer($dplayer)->hasPermission("myplot.admin.bypassdeny")) {
            $sender->sendMessage($this->translateString("denyplayer.cannotdeny", [$dplayer]));
            $dp->sendMessage($this->translateString("denyplayer.attempteddeny", [$sender->getName()]));
            return true;
        }
        if (!$plot->denyPlayer($dplayer)) {
            $sender->sendMessage($this->translateString("denyplayer.alreadydenied", [$dplayer]));
            return true;
        }
        if ($this->getPlugin()->savePlot($plot)) {
            $sender->sendMessage($this->translateString("denyplayer.success1", [$dplayer]));
            $dp->sendMessage($this->translateString("denyplayer.success2", [$plot->X,$plot->Z,$sender->getName()]));
        } else {
            $sender->sendMessage(TextFormat::RED . $this->translateString("error"));
        }
        return true;
    }
}
