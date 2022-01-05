<?php

namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class RateSubcommand extends SubCommand
{

    public function canUse(CommandSender $sender): bool
    {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.rate");
    }

    /**
     * @param Player $sender
     * @param string[] $args
     *
     * @return bool
     */
    public function execute(CommandSender $sender, array $args): bool
    {
        if (count($args) <= 0) {
            return false;
        }
        $plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
        if ($plot === null) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
            return true;
        }

        if ((is_numeric($args[0]) && is_float($args[0])) || $args[0] <= 0 || $args[0] > 5 || !is_numeric($args[0])) {
            $sender->sendMessage($this->translateString("rate.error"));
            return true;
        }

        $this->getOwningPlugin()->ratePlot($plot, (int)$args[0]);
        $sender->sendMessage($this->translateString("rate.success", [(string)$args[0]]));
        return true;
    }

    public function getForm(?Player $player = null): ?MyPlotForm
    {
        return null;
    }
}