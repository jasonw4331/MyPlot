<?php
declare(strict_types=1);
namespace MyPlot\subcommand;


use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class FindSubCommand extends SubCommand
{
    /**
     * @param CommandSender $sender
     *
     * @return bool
     */
    public function canUse(CommandSender $sender) : bool {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.find");
    }

    /**
     * @param Player $sender
     * @param string[] $args
     *
     * @return bool
     */
    public function execute(CommandSender $sender, array $args) : bool {
        if(!isset($args[0])) {
            return false;
        }

        foreach($this->getPlugin()->getPlotLevels() as $levelName => $levelSettings) {
            $plots = $this->getPlugin()->getPlotsOfPlayer($args[0], $levelName);
            if(empty($plots)) {
                $sender->sendMessage(TextFormat::RED . $this->translateString("find.noplots"));
                return true;
            }
            foreach($plots as $plot)
            {
                $sender->sendMessage($this->translateString("find.about", [TextFormat::GREEN . $plot->id . TextFormat::WHITE, TextFormat::GREEN . $args[0] . TextFormat::WHITE]));
                $sender->sendMessage($this->translateString("find.position", [TextFormat::GREEN . $plot->X . ";" . TextFormat::GREEN . $plot->Z . TextFormat::WHITE]));
                $sender->sendMessage($this->translateString("find.plotname", [TextFormat::GREEN . $plot->name . TextFormat::WHITE]));
                $sender->sendMessage($this->translateString("find.helpers", [TextFormat::GREEN . implode(", ", $plot->helpers) . TextFormat::WHITE]));
                $sender->sendMessage($this->translateString("find.denied", [TextFormat::GREEN . implode(", ", $plot->denied) . TextFormat::WHITE]));
                $sender->sendMessage($this->translateString("find.biome", [TextFormat::GREEN . $plot->biome  . TextFormat::WHITE]));
            }
            return true;
        }

        return true;
    }

}