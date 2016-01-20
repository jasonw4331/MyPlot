<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class InfoSubCommand extends SubCommand
{
    public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.info");
    }

    public function getAliases() {
        return [];
    }

    public function execute(CommandSender $sender, array $args) {
        if (!empty($args)) {
            return false;
        }
        $player = $sender->getServer()->getPlayer($sender->getName());
        $plot = $this->getPlugin()->getPlotByPosition($player->getPosition());
        if ($plot === null) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
            return true;
        }
        $sender->sendMessage(TextFormat::DARK_GREEN . $this->translateString("info.about", [TextFormat::WHITE . $plot]));
        $sender->sendMessage(TextFormat::DARK_GREEN . $this->translateString("info.plotname", [TextFormat::WHITE . $plot->name]));
        $sender->sendMessage(TextFormat::DARK_GREEN . $this->translateString("info.owner", [TextFormat::WHITE . $plot->owner]));
        $helpers = implode(", ", $plot->helpers);
        $sender->sendMessage(TextFormat::DARK_GREEN . $this->translateString("info.helpers", [TextFormat::WHITE . $helpers]));
        $sender->sendMessage(TextFormat::DARK_GREEN . $this->translateString("info.biome", [TextFormat::WHITE . $plot->biome]));
        return true;
    }
}