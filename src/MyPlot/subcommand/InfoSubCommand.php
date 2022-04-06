<?php
declare(strict_types=1);

namespace MyPlot\subcommand;

use MyPlot\forms\subforms\InfoForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

class InfoSubCommand extends SubCommand{
	public function canUse(CommandSender $sender) : bool{
		if(!$sender->hasPermission("myplot.command.info")){
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
			function() use ($sender, $args) : \Generator{
				if(isset($args[0])){
					if(isset($args[1]) and is_numeric($args[1])){
						$key = max(((int) $args[1] - 1), 1);
						$plots = yield from $this->internalAPI->generatePlotsOfPlayer($args[0], null);
						if(isset($plots[$key])){
							$plot = $plots[$key];
							$sender->sendMessage($this->translateString("info.about", [TextFormat::GREEN . $plot]));
							$sender->sendMessage($this->translateString("info.owner", [TextFormat::GREEN . $plot->owner]));
							$sender->sendMessage($this->translateString("info.plotname", [TextFormat::GREEN . $plot->name]));
							$helpers = implode(", ", $plot->helpers);
							$sender->sendMessage($this->translateString("info.helpers", [TextFormat::GREEN . $helpers]));
							$denied = implode(", ", $plot->denied);
							$sender->sendMessage($this->translateString("info.denied", [TextFormat::GREEN . $denied]));
							$sender->sendMessage($this->translateString("info.biome", [TextFormat::GREEN . $plot->biome]));
							return;
						}
						$sender->sendMessage(TextFormat::RED . $this->translateString("info.notfound"));
						return;
					}
					$sender->sendMessage($this->translateString("subcommand.usage", [$this->getUsage()]));
					return;
				}
				$plot = yield from $this->internalAPI->generatePlotByPosition($sender->getPosition());
				if($plot === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
					return;
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
		);
		return true;
	}

	public function getFormClass() : ?string{
		return InfoForm::class;
	}
}