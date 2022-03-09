<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

class HomesSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.homes");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool{
		Await::f2c(
			function() use ($sender, $args) : \Generator{
				$levelName = $args[0] ?? $sender->getWorld()->getFolderName();
				if($this->internalAPI->getLevelSettings($levelName) === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("error", [$levelName]));
					return;
				}
				$plots = yield $this->internalAPI->generatePlotsOfPlayer($sender->getName(), $levelName);
				if(count($plots) === 0){
					$sender->sendMessage(TextFormat::RED . $this->translateString("homes.noplots"));
					return;
				}
				$sender->sendMessage(TextFormat::DARK_GREEN . $this->translateString("homes.header"));
				for($i = 0; $i < count($plots); $i++){
					$plot = $plots[$i];
					$message = TextFormat::DARK_GREEN . ($i + 1) . ") ";
					$message .= TextFormat::WHITE . $plot->levelName . " " . $plot;
					if($plot->name !== ""){
						$message .= " = " . $plot->name;
					}
					$sender->sendMessage($message);
				}
			}
		);
		return true;
	}
}