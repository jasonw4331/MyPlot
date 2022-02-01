<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\FillForm;
use MyPlot\Plot;
use pocketmine\block\Air;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
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
		$plot = $this->plugin->getPlotByPosition($sender->getPosition());
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED.$this->translateString("notinplot"));
			return true;
		}
		if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.fill")) {
			$sender->sendMessage(TextFormat::RED.$this->translateString("notowner"));
			return true;
		}

		if(($item = StringToItemParser::getInstance()->parse($args[0])) instanceof Item and $item->getBlock() instanceof Air) {
			$maxBlocksPerTick = (int)$this->plugin->getConfig()->get("FillBlocksPerTick", 256);
			if($this->plugin->fillPlot($plot, $item->getBlock(), $maxBlocksPerTick)) {
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
		if($this->plugin->getPlotByPosition($player->getPosition()) instanceof Plot) {
			return new FillForm();
		}
		return null;
	}
}