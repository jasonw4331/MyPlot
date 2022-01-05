<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\FillForm;
use MyPlot\Plot;
use pocketmine\block\BlockLegacyIds;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\LegacyItemIdToStringIdMap;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class FillSubCommand extends SubCommand {
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
		if(count($args) < 1) {
			return false;
		}
		$plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED.$this->translateString("notinplot"));
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.fill")) {
			$sender->sendMessage(TextFormat::RED.$this->translateString("notowner"));
			return true;
		}
		if(($item = ItemFactory::getInstance()->get((int)$args[0])) instanceof Item and $item->getBlock()->getId() !== BlockLegacyIds::AIR) {
			$maxBlocksPerTick = (int)$this->getPlugin()->getConfig()->get("FillBlocksPerTick", 1024);
			if($this->getPlugin()->fillPlot($plot, $item->getBlock(), $maxBlocksPerTick)) {
				$sender->sendMessage($this->translateString("fill.success", [$item->getBlock()->getName()]));
			}else {
				$sender->sendMessage(TextFormat::RED.$this->translateString("error"));
			}
		}else {
			return false;
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($this->getPlugin()->getPlotByPosition($player->getPosition()) instanceof Plot) {
			return new FillForm($player);
		}
		return null;
	}
}