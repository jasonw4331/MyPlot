<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class DisposeSubCommand extends SubCommand
{
    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.dispose");
    }

    public function execute(CommandSender $sender, array $args) {
        $confirm = (count($args) == 1 and $args[0] == $this->translateString("confirm"));
        if (count($args) != 0 and !$confirm) {
            return false;
        }

        $player = $sender->getServer()->getPlayer($sender->getName());
        $plot = $this->getPlugin()->getPlotByPosition($player->getPosition());
        if ($plot === null) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
            return true;
        }
        if ($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.dispose")) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
            return true;
        }

        if ($confirm) {
            $economy = $this->getPlugin()->getEconomyProvider();
            $price = $this->getPlugin()->getLevelSettings($plot->levelName)->disposePrice;
            if ($economy !== null and !$economy->reduceMoney($player, $price)) {
                $sender->sendMessage(TextFormat::RED . $this->translateString("dispose.nomoney"));
                return true;
            }

            if ($this->getPlugin()->disposePlot($plot)) {
                $sender->sendMessage($this->translateString("dispose.success"));
            } else {
                $sender->sendMessage(TextFormat::RED . $this->translateString("error"));
            }
        } else {
            $plotId = TextFormat::GREEN . $plot . TextFormat::WHITE;
            $sender->sendMessage($this->translateString("dispose.confirm", [$plotId]));
        }
        return true;
    }
}
