<?php
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class InfoSubCommand extends SubCommand
{
	/**
	 * @param CommandSender $sender
	 * @return bool
	 */
	public function canUse(CommandSender $sender) {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.info");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) {
		$plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
		if ($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		$sender->sendMessage($this->translateString("info.about", [TextFormat::GREEN . $plot]));
		$sender->sendMessage($this->translateString("info.owner", [TextFormat::GREEN . $plot->owner]));
		$sender->sendMessage($this->translateString("info.plotname", [TextFormat::GREEN . $plot->name]));
		$helpers = implode(", ", $plot->helpers);
		$sender->sendMessage($this->translateString("info.helpers", [TextFormat::GREEN . $helpers]));
		$denied = implode(", ", $plot->denied);
		$sender->sendMessage($this->translateString("info.denied", [TextFormat::GREEN . $denied]));
		$sender->sendMessage($this->translateString("info.biome", [TextFormat::GREEN . $plot->biome]));
		return true;
	}
}