<?php
declare(strict_types=1);

namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

class MergeSubCommand extends SubCommand{
	public function canUse(CommandSender $sender) : bool{
		if(!$sender->hasPermission("myplot.command.merge")){
			return false;
		}
		if($sender instanceof Player){
			$pos = $sender->getPosition();
			$plotLevel = $this->internalAPI->getLevelSettings($sender->getWorld()->getFolderName());
			if($this->internalAPI->getPlotFast($pos->x, $pos->z, $plotLevel) === null){
				return false;
			}
		}

		return true;
	}

	/**
	 * @param Player   $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool{
		Await::f2c(
			function() use ($sender) : \Generator{
				$plot = yield from $this->internalAPI->generatePlotByPosition($sender->getPosition());
				if($plot === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
					return;
				}
				if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.merge")){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
					return;
				}
				if(!isset($args[0])){
					$plotId = TextFormat::GREEN . $plot . TextFormat::WHITE;
					$sender->sendMessage($this->translateString("merge.confirmface", [$plotId]));
					return;
				}elseif($args[0] === $this->translateString("confirm")){
					$rotation = ($sender->getLocation()->getYaw() - 180) % 360;
					if($rotation < 0){
						$rotation += 360.0;
					}
					if((0 <= $rotation and $rotation < 45) or (315 <= $rotation and $rotation < 360)){
						$direction = Facing::NORTH; //North
						$args[0] = $this->translateString("merge.north");
					}elseif(45 <= $rotation and $rotation < 135){
						$direction = Facing::EAST; //East
						$args[0] = $this->translateString("merge.east");
					}elseif(135 <= $rotation and $rotation < 225){
						$direction = Facing::SOUTH; //South
						$args[0] = $this->translateString("merge.south");
					}elseif(225 <= $rotation and $rotation < 315){
						$direction = Facing::WEST; //West
						$args[0] = $this->translateString("merge.west");
					}else{
						$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
						return;
					}
				}else{
					switch(strtolower($args[0])){
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
							return;
					}
					if(!isset($args[1]) or $args[1] !== $this->translateString("confirm")){
						$plotId = TextFormat::GREEN . $plot . TextFormat::WHITE;
						$sender->sendMessage($this->translateString("merge.confirmarg", [$plotId, $args[0], implode(' ', $args) . " " . $this->translateString("confirm")]));
						return;
					}
				}
				$maxBlocksPerTick = $this->plugin->getConfig()->get("ClearBlocksPerTick", 256);
				if(!is_int($maxBlocksPerTick))
					$maxBlocksPerTick = 256;
				if(yield from $this->internalAPI->generateMergePlots($plot, $direction, $maxBlocksPerTick)){
					$plot = TextFormat::GREEN . $plot . TextFormat::WHITE;
					$sender->sendMessage($this->translateString("merge.success", [$plot, $args[0]]));
				}else{
					$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
				}
			}
		);
		return true;
	}
}