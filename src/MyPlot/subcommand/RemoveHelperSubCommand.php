<?php
namespace MyPlot\subcommand;

use MyPlot\events\MyPlotHelperEvent;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class RemoveHelperSubCommand extends SubCommand
{
    /**
	 * @param CommandSender $sender
	 * @return bool
	 */public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.removehelper");
    }

    /**
	 * @param Player $sender
	 * @param string[] $args
	 * @return bool
	 */public function execute(CommandSender $sender, array $args) {
        if (count($args) !== 1) {
            return false;
        }
        $helper = $args[0];
        $plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
        if ($plot === null) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
            return true;
        }
        if ($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.removehelper")) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
            return true;
        }
        $this->getPlugin()->getServer()->getPluginManager()->callEvent(
			($ev = new MyPlotHelperEvent($this->getPlugin(), "MyPlot", $plot, MyPlotHelperEvent::REMOVE, $helper->getName()))
		);
		if($ev->isCancelled()) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
			return true;
		}if (!$plot->removeHelper($helper)) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("removehelper.notone", [$helper]));
            return true;
        }
        if ($this->getPlugin()->savePlot($plot)) {
            $sender->sendMessage($this->translateString("removehelper.success", [$helper]));
        } else {
            $sender->sendMessage(TextFormat::RED . $this->translateString("error"));
        }
        return true;
    }
}