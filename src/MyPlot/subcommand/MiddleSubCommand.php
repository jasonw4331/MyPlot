<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class MiddleSubCommand extends SubCommand
{

    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and ($sender->hasPermission("myplot.command.middle"));
    }

    public function execute(CommandSender $sender, array $args) {
        if($sender instanceof Player);
        if(count($args) != 0) {
            return false;
        }
        $plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
        if ($plot === null) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
            return true;
        }
        if ($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.middle")) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
            return true;
        }
        if($this->getPlugin()->teleportMiddle($plot, $sender)) {
            $sender->sendMessage(TextFormat::GREEN . $this->translateString("middle.success"));
        }
        return true;
    }
}