<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\InfoForm;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class InfoSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.info");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(isset($args[0])) {
			if(isset($args[1]) and is_numeric($args[1])) {
				$key = max(((int) $args[1] - 1), 1);
				/** @var Plot[] $plots */
				$plots = [];
				foreach($this->plugin->getPlotLevels() as $levelName => $settings) {
					$plots = array_merge($plots, $this->plugin->getPlotsOfPlayer($args[0], $levelName));
				}
				if(isset($plots[$key])) {
					$plot = $plots[$key];
					$sender->sendMessage($this->translateString("info.about", [TextFormat::GREEN . $plot]));
					$sender->sendMessage($this->translateString("info.owner", [TextFormat::GREEN . $plot->owner]));
					$sender->sendMessage($this->translateString("info.plotname", [TextFormat::GREEN . $plot->name]));
					$helpers = implode(", ", $plot->helpers);
					$sender->sendMessage($this->translateString("info.helpers", [TextFormat::GREEN . $helpers]));
					$denied = implode(", ", $plot->denied);
					$sender->sendMessage($this->translateString("info.denied", [TextFormat::GREEN . $denied]));
					$sender->sendMessage($this->translateString("info.biome", [TextFormat::GREEN . $plot->biome]));
				}else{
					$sender->sendMessage(TextFormat::RED . $this->translateString("info.notfound"));
				}
			}else{
				return false;
			}
		}else{
			$plot = $this->plugin->getPlotByPosition($sender->getPosition());
			if($plot === null) {
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
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($player !== null and ($plot = $this->plugin->getPlotByPosition($player->getPosition())) instanceof Plot)
			return new InfoForm($plot);
		return null;
	}
}