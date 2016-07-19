<?php
namespace MyPlot\subcommand;

use MyPlot\task\DoneMarkTask;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class DoneSubCommand extends SubCommand
{

    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.done");
    }

    public function execute(CommandSender $sender, array $args) {
        if($sender instanceof Player);
        if (count($args) !== 0) {
            return true;
        }
        $plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
        if ($plot === null) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
            return true;
        }
        if ($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.done")) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
            return true;
        }
        if($plot->toggleDone()) {
            $plot->tid = $this->getPlugin()->getServer()->getScheduler()->scheduleRepeatingTask(new DoneMarkTask($this->getPlugin(),$plot),20)->getTaskId();
            $sender->sendMessage($this->translateString("done.completed"));
        } elseif(isset($plot->tid)) {
            $this->getPlugin()->getServer()->getScheduler()->cancelTask($plot->tid);
            $sender->sendMessage($this->translateString("done.undone"));
        } else {
            $sender->sendMessage($this->translateString("error"));
        }
        return true;
    }
}