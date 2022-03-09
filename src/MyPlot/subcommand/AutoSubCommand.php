<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

class AutoSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.auto");
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
				$levelName = $sender->getWorld()->getFolderName();
				if($this->internalAPI->getLevelSettings($levelName) === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("auto.notplotworld"));
					return;
				}
				$plot = yield $this->internalAPI->generateNextFreePlot($levelName, 0);
				if($plot === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("auto.noplots"));
					return;
				}
				if(yield $this->internalAPI->generatePlayerTeleport($sender, $plot, true)){
					$sender->sendMessage($this->translateString("auto.success", [$plot->X, $plot->Z]));
					$cmd = new ClaimSubCommand($this->plugin, $this->internalAPI, "claim");
					if(isset($args[0]) and strtolower($args[0]) === "true" and $cmd->canUse($sender))
						$cmd->execute($sender, isset($args[1]) ? [$args[1]] : []);
					return;
				}
				$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
			}
		);
		return true;
	}
}