<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class ListSubCommand extends SubCommand {
	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.list");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if($sender->hasPermission("myplot.admin.list")) {
			if(!empty($args)) {
				foreach($this->getPlugin()->getPlotLevels() as $levelName => $settings) {
					$plots = $this->getPlugin()->getPlotsOfPlayer($args[0], $levelName);
					foreach($plots as $plot) {
						$name = $plot->name;
						$x = $plot->X;
						$z = $plot->Z;
						$sender->sendMessage(TF::YELLOW . $this->translateString("list.found", [$name, $x, $z]));
					}
				}
			}else{
				foreach($this->getPlugin()->getPlotLevels() as $levelName => $settings) {
					$plots = $this->getPlugin()->getPlotsOfPlayer($sender->getName(), $levelName);
					foreach($plots as $plot) {
						$name = $plot->name;
						$x = $plot->X;
						$z = $plot->Z;
						$sender->sendMessage(TF::YELLOW . $this->translateString("list.found", [$name, $x, $z]));
					}
					return true;
				}
			}
		}elseif($sender->hasPermission("myplot.command.list")) {
			foreach($this->getPlugin()->getPlotLevels() as $levelName => $settings) {
				$plots = $this->getPlugin()->getPlotsOfPlayer($sender->getName(), $levelName);
				foreach($plots as $plot) {
					$name = $plot->name;
					$x = $plot->X;
					$z = $plot->Z;
					$sender->sendMessage(TF::YELLOW . $this->translateString("list.found", [$name, $x, $z]));
				}
			}
		}
		return true;
	}
}