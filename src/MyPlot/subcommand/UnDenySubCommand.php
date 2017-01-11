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
        foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $player) {
            if(similar_text($dplayer,strtolower($player->getName()))/strlen($player->getName()) >= 0.3 ) { //TODO correct with a better system
                $dplayer = $this->getPlugin()->getServer()->getPlayer($dplayer);
                break;
            }
        }
        if(!$dplayer instanceof Player) {
            $sender->sendMessage($this->translateString("denyplayer.notaplayer"));
            return true;
        }
        if (!$plot->unDenyPlayer($dplayer->getName())) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("undenyplayer.notdenied", [$dplayer->getName()]));
            return true;
        }
        if ($this->getPlugin()->savePlot($plot)) {
            $sender->sendMessage($this->translateString("undenyplayer.success1", [$dplayer->getName()]));
            $dp->sendMessage($this->translateString("undenyplayer.success2", [$plot->X,$plot->Z,$sender->getName()]));
        } else {
            $sender->sendMessage(TextFormat::RED . $this->translateString("error"));
        }
        return true;
    }
}
