<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\MyPlot;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;

class MergeSubCommand extends SubCommand
{
	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and ($sender->hasPermission("myplot.command.merge"));
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		$plot = $this->getPlugin()->getPlotByPosition($sender);
		if($plot === null) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du stehst auf keinem Grundstück!");
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.merge")) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du bist nicht Besitzer dieses Grundstücks!");
			return true;
		}
		if(!isset($args[0])) {
            $sender->sendMessage(MyPlot::PREFIX.C::RED."Bitte gebe ".C::YELLOW."/plot merge confirm".C::RED." ein, um zu bestätigen, dass das Grundstück gemerged wird. Das Mergen kann momentan noch nicht rückgängig gemacht werden!");
			return true;
		}elseif($args[0] === $this->translateString("confirm")) {
			$rotation = ($sender->getYaw() - 180) % 360;
			if($rotation < 0) {
				$rotation += 360.0;
			}
			if((0 <= $rotation and $rotation < 45) or (315 <= $rotation and $rotation < 360)) {
				$direction = Vector3::SIDE_NORTH; //North
				$args[0] = $this->translateString("merge.north");
			}elseif(45 <= $rotation and $rotation < 135) {
				$direction = Vector3::SIDE_EAST; //East
				$args[0] = $this->translateString("merge.east");
			}elseif(135 <= $rotation and $rotation < 225) {
				$direction = Vector3::SIDE_SOUTH; //South
				$args[0] = $this->translateString("merge.south");
			}elseif(225 <= $rotation and $rotation < 315) {
				$direction = Vector3::SIDE_WEST; //West
				$args[0] = $this->translateString("merge.west");
			}else{
				$sender->sendMessage(C::RED . $this->translateString("error"));
				return true;
			}
		}else{
			switch(strtolower($args[0])) {
				case "north":
				case "-z":
				case "z-":
				case $this->translateString("merge.north"):
					$direction = Vector3::SIDE_NORTH;
				break;
				case "east":
				case "+x":
				case "x+":
				case $this->translateString("merge.east"):
					$direction = Vector3::SIDE_EAST;
				break;
				case "south":
				case "+z":
				case "z+":
				case $this->translateString("merge.south"):
					$direction = Vector3::SIDE_SOUTH;
				break;
				case "west":
				case "-x":
				case "x-":
				case $this->translateString("merge.west"):
					$direction = Vector3::SIDE_WEST;
				break;
				default:
					$sender->sendMessage(C::RED . $this->translateString("merge.direction"));
					return true;
			}
            if(!isset($args[1]) or $args[1] !== $this->translateString("confirm")) {
                $sender->sendMessage(MyPlot::PREFIX.C::RED."Bitte gebe ".C::YELLOW."/plot merge confirm".C::RED." ein, um zu bestätigen, dass das Grundstück gemerged wird. Das Mergen kann momentan noch nicht rückgängig gemacht werden!");
                return true;
			}
		}
		$maxBlocksPerTick = (int) $this->getPlugin()->getConfig()->get("ClearBlocksPerTick", 256);
		if($this->getPlugin()->mergePlots($plot, $direction, $maxBlocksPerTick)) {
			$plot = C::GREEN . $plot . C::WHITE;
            $sender->sendMessage(MyPlot::PREFIX . C::GREEN."Das Grundstück wird nun gemerged.");
			return true;
		}else{
			$sender->sendMessage(C::RED . $this->translateString("error"));
			return true;
		}
	}

    public function getForm(?Player $player = null) : ?MyPlotForm {
        return null;
    }
}