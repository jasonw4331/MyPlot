<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use MyPlot\Plot;

class HomesSubCommand extends SubCommand
{
    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.homes");
    }

    public function execute(CommandSender $sender, array $args) {
        if (!empty($args)) {
            return false;
        }
        $plots = $this->getPlugin()->getProvider()->getPlotsByOwner($sender->getName());
        if (empty($plots)) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("homes.noplots"));
            return true;
        }
        $sender->sendMessage(TextFormat::DARK_GREEN . $this->translateString("homes.header"));

        for ($i = 0; $i < count($plots); $i++) {
            $plot = $plots[$i];
            $message = TextFormat::DARK_GREEN . ($i + 1) . ") ";
            $message .= TextFormat::WHITE . $plot->levelName . " " . $plot;
            if ($plot->name !== "") {
                $message .= " = " . $plot->name;
            }
            $sender->sendMessage($message);
        }
        return true;
    }
}