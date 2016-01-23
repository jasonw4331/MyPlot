<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ClearSubCommand extends SubCommand
{
    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.clear");
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
        if ($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.clear")) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
            return true;
        }

        if ($confirm) {
            $economy = $this->getPlugin()->getEconomyProvider();
            $price = $this->getPlugin()->getLevelSettings($plot->levelName)->clearPrice;
            if ($economy !== null and !$economy->reduceMoney($player, $price)) {
                $sender->sendMessage(TextFormat::RED . $this->translateString("clear.nomoney"));
                return true;
            }

            $maxBlocksPerTick = $this->getPlugin()->getConfig()->get("ClearBlocksPerTick", 256);
            if ($this->getPlugin()->clearPlot($plot, $maxBlocksPerTick)) {
                $sender->sendMessage($this->translateString("clear.success"));
            } else {
                $sender->sendMessage(TextFormat::RED . $this->translateString("error"));
            }
        } else {
            $plotId = TextFormat::GREEN . $plot . TextFormat::WHITE;
            $sender->sendMessage($this->translateString("clear.confirm", [$plotId]));
        }
        return true;
    }
}
