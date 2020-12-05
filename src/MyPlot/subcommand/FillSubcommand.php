<?php
declare(strict_types=1);

namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\FillForm;
use MyPlot\Plot;
use pocketmine\block\Block;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class FillSubcommand extends SubCommand {
	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.fill");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(empty($args)) {
			return false;
		}
		$plot = $this->getPlugin()->getPlotByPosition($sender);
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED.$this->translateString("notinplot"));
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.fill")) {
			$sender->sendMessage(TextFormat::RED.$this->translateString("notowner"));
			return true;
		}
		if(Item::fromString($args[0]) !== null && Item::fromString($args[0])->getBlock()->getId() !== Block::AIR) {
			$maxBlocksPerTick = (int)$this->getPlugin()->getConfig()->get("FillBlocksPerTick", 256);
			if($this->getPlugin()->fillPlot($plot, Item::fromString($args[0])->getBlock(), $maxBlocksPerTick)) {
				$sender->sendMessage($this->translateString("fill.success", [Item::fromString($args[0])->getBlock()->getName()]));
			}else {
				$sender->sendMessage(TextFormat::RED.$this->translateString("error"));
			}
		}else {
			return false;
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($this->getPlugin()->getPlotByPosition($player) instanceof Plot) {
			return new FillForm($player);
		}
		return null;
	}
}