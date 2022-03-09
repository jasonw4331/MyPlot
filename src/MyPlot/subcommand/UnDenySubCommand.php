<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\UndenyPlayerForm;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

class UnDenySubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.undenyplayer");
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
				if(count($args) === 0){
					$sender->sendMessage($this->translateString("subcommand.usage", [$this->getUsage()]));
					return;
				}
				$dplayerName = $args[0];
				$plot = yield $this->internalAPI->generatePlotByPosition($sender->getPosition());
				if($plot === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
					return;
				}
				if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.undenyplayer")){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
					return;
				}
				$dplayer = $this->plugin->getServer()->getPlayerByPrefix($dplayerName);
				if($dplayer === null)
					$dplayer = $this->plugin->getServer()->getOfflinePlayer($dplayerName);
				if(yield $this->internalAPI->generateRemovePlotDenied($plot, $dplayer->getName())){
					$sender->sendMessage($this->translateString("undenyplayer.success1", [$dplayer->getName()]));
					if($dplayer instanceof Player){
						$dplayer->sendMessage($this->translateString("undenyplayer.success2", [$plot->X, $plot->Z, $sender->getName()]));
					}
				}else{
					$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
				}
			}
		);
		return true;
	}

	public function getFormClass() : ?string{
		return UndenyPlayerForm::class;
	}
}