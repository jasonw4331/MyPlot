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
        $confirm = (count($args) == 2 and $args[1] == $this->translateString("confirm"));
        if (count($args) != 1 and !$confirm) {
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

        $maxPlots = $this->getPlugin()->getMaxPlotsOfPlayer($newOwner);
        $plotsOfPlayer = count($this->getPlugin()->getProvider()->getPlotsByOwner($newOwner->getName()));
        if ($plotsOfPlayer >= $maxPlots) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("give.maxedout", [$maxPlots]));
            return true;
        }

        if ($confirm) {
            $plot->owner = $newOwner->getName();
            if ($this->getPlugin()->getProvider()->savePlot($plot)) {
                $plotId = TextFormat::GREEN . $plot . TextFormat::WHITE;
                $oldOwnerName = TextFormat::GREEN . $sender->getName() . TextFormat::WHITE;
                $newOwnerName = TextFormat::GREEN . $newOwner->getName() . TextFormat::WHITE;
                $sender->sendMessage($this->translateString("give.success", [$newOwnerName]));
                $newOwner->sendMessage($this->translateString("give.received", [$oldOwnerName, $plotId]));
            } else {
                $sender->sendMessage(TextFormat::RED . $this->translateString("error"));
            }
        } else {
            $plotId = TextFormat::GREEN . $plot . TextFormat::WHITE;
            $newOwnerName = TextFormat::GREEN . $newOwner->getName() . TextFormat::WHITE;
            $sender->sendMessage($this->translateString("give.confirm", [$plotId, $newOwnerName]));
        }
        return true;
    }
}
