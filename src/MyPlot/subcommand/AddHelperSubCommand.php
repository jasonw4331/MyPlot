<?php
namespace MyPlot\subcommand;

use MyPlot\events\MyPlotHelperEvent;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class AddHelperSubCommand extends SubCommand
{
    /**
	 * @param CommandSender $sender
	 * @return bool
	 */public function canUse(CommandSender $sender) {
        return ($sender instanceof Player) and $sender->hasPermission("myplot.command.addhelper");
    }

    /**
	 * @param Player $sender
	 * @param string[] $args
	 * @return bool
	 */public function execute(CommandSender $sender, array $args) {
        if (count($args) !== 1)
            return false;

        $helper = $args[0];
        $plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
        if ($plot === null) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
            return true;
        }
        if ($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.addhelper")) {
            $sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
            return true;
        }
	    foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $player) {
		    if(similar_text($helper,strtolower($player->getName()))/strlen($player->getName()) >= 0.3 ) { //TODO correct with a better system
			    $helper = $this->getPlugin()->getServer()->getPlayer($helper);
			    break;
		    }
	    }
	    if(!$helper instanceof Player) {
		    $sender->sendMessage($this->translateString("addhelper.notaplayer"));
		    return true;
	    }
	    $this->getPlugin()->getServer()->getPluginManager()->callEvent(
	    	($ev = new MyPlotHelperEvent($this->getPlugin(), "MyPlot", $plot, MyPlotHelperEvent::ADD, $helper->getName()))
	    );
        if($ev->isCancelled()) {
	        $sender->sendMessage(TextFormat::RED . $this->translateString("error"));
        	return true;
        }
        if (!$ev->getPlot()->addHelper($ev->getHelper())) {
            $sender->sendMessage($this->translateString("addhelper.alreadyone", [$helper->getName()]));
            return true;
        }
        if ($this->getPlugin()->savePlot($ev->getPlot())) {
            $sender->sendMessage($this->translateString("addhelper.success", [$helper->getName()]));
        } else {
            $sender->sendMessage(TextFormat::RED . $this->translateString("error"));
        }
        return true;
    }
}