<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use pocketmine\command\CommandSender;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

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
		$plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.merge")) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		if(!isset($args[0])) {
			$plotId = TextFormat::GREEN . $plot . TextFormat::WHITE;
			$sender->sendMessage($this->translateString("merge.confirmface", [$plotId]));
			return true;
		}elseif($args[0] === $this->translateString("confirm")) {
			$rotation = ($sender->getLocation()->getYaw() - 180) % 360;
			if($rotation < 0) {
				$rotation += 360.0;
			}
			if((0 <= $rotation and $rotation < 45) or (315 <= $rotation and $rotation < 360)) {
				$direction = Facing::NORTH; //North
				$args[0] = $this->translateString("merge.north");
			}elseif(45 <= $rotation and $rotation < 135) {
				$direction = Facing::EAST; //East
				$args[0] = $this->translateString("merge.east");
			}elseif(135 <= $rotation and $rotation < 225) {
				$direction = Facing::SOUTH; //South
				$args[0] = $this->translateString("merge.south");
			}elseif(225 <= $rotation and $rotation < 315) {
				$direction = Facing::WEST; //West
				$args[0] = $this->translateString("merge.west");
			}else{
				$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
				return true;
			}
		}else{
			switch(strtolower($args[0])) {
				case "north":
				case "-z":
				case "z-":
				case $this->translateString("merge.north"):
					$direction = Facing::NORTH;
					$args[0] = $this->translateString("merge.north");
				break;
				case "east":
				case "+x":
				case "x+":
				case $this->translateString("merge.east"):
					$direction = Facing::EAST;
					$args[0] = $this->translateString("merge.east");
				break;
				case "south":
				case "+z":
				case "z+":
				case $this->translateString("merge.south"):
					$direction = Facing::SOUTH;
					$args[0] = $this->translateString("merge.south");
				break;
				case "west":
				case "-x":
				case "x-":
				case $this->translateString("merge.west"):
					$direction = Facing::WEST;
					$args[0] = $this->translateString("merge.west");
				break;
				default:
					$sender->sendMessage(TextFormat::RED . $this->translateString("merge.direction"));
					return true;
			}
			if(!isset($args[1]) or $args[1] !== $this->translateString("confirm")) {
				$plotId = TextFormat::GREEN . $plot . TextFormat::WHITE;
				$sender->sendMessage($this->translateString("merge.confirmarg", [$plotId, $args[0], implode(' ', $args)." ".$this->translateString("confirm")]));
				return true;
			}
		}
		$maxBlocksPerTick = (int) $this->getPlugin()->getConfig()->get("ClearBlocksPerTick", 256);
		if($this->getPlugin()->mergePlots($plot, $direction, $maxBlocksPerTick)) {
			$plot = TextFormat::GREEN . $plot . TextFormat::WHITE;
			$sender->sendMessage($this->translateString("merge.success", [$plot, $args[0]]));
			return true;
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
			return true;
		}
	}

    public function getForm(?Player $player = null) : ?MyPlotForm {
        return null;
    }
}