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
        $player = $sender->getServer()->getPlayer($sender->getName());
        $levelName = $player->getLevel()->getName();
        $plots = $this->getPlugin()->getProvider()->getPlotsByOwner($sender->getName());
        if (empty($plots)) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("homes.noplots"));
            return true;
        }
        $sender->sendMessage(TextFormat::DARK_GREEN . $this->translateString("homes.header"));

        usort($plots, function ($plot1, $plot2) {
            /** @var $plot1 Plot */
            /** @var $plot2 Plot */
            if ($plot1->levelName == $plot2->levelName) {
                return 0;
            }
            return ($plot1->levelName < $plot2->levelName) ? -1 : 1;
        });

        for ($i = 0; $i < count($plots); $i++) {
            $plot = $plots[$i];
            $message = TextFormat::DARK_GREEN . ($i + 1) . ") ";
            $message .= TextFormat::WHITE . $levelName . " " . $plot;
            if ($plot->name !== "") {
                $message .= " = " . $plot->name;
            }
            $sender->sendMessage($message);
        }
        return true;
    }
}