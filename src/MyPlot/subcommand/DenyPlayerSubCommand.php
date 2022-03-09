<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\DenyPlayerForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

class DenyPlayerSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.denyplayer");
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
				$dplayer = $args[0];
				$plot = yield $this->internalAPI->generatePlotByPosition($sender->getPosition());
				if($plot === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
					return;
				}
				if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.denyplayer")){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
					return;
				}
				if($dplayer === "*"){
					if(yield $this->internalAPI->generateAddPlotDenied($plot, $dplayer)){
						$sender->sendMessage($this->translateString("denyplayer.success1", [$dplayer]));
						foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
							if((yield $this->internalAPI->generatePlotBB($plot))->isVectorInside($player) and !($player->getName() === $plot->owner) and !$player->hasPermission("myplot.admin.denyplayer.bypass") and !$plot->isHelper($player->getName()))
								$this->internalAPI->generatePlayerTeleport($player, $plot, false);
							else{
								$sender->sendMessage($this->translateString("denyplayer.cannotdeny", [$player->getName()]));
								$player->sendMessage($this->translateString("denyplayer.attempteddeny", [$sender->getName()]));
							}
						}
					}else{
						$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
					}
					return;
				}

				$dplayer = $this->plugin->getServer()->getPlayerByPrefix($dplayer);
				if(!$dplayer instanceof Player){
					$sender->sendMessage($this->translateString("denyplayer.notaplayer"));
					return;
				}
				if($dplayer->hasPermission("myplot.admin.denyplayer.bypass") or $dplayer->getName() === $plot->owner){
					$sender->sendMessage($this->translateString("denyplayer.cannotdeny", [$dplayer->getName()]));
					$dplayer->sendMessage($this->translateString("denyplayer.attempteddeny", [$sender->getName()]));
					return;
				}
				if(yield $this->plugin->addPlotDenied($plot, $dplayer->getName())){
					$sender->sendMessage($this->translateString("denyplayer.success1", [$dplayer->getName()]));
					$dplayer->sendMessage($this->translateString("denyplayer.success2", [$plot->X, $plot->Z, $sender->getName()]));
					if((yield $this->internalAPI->generatePlotBB($plot))->isVectorInside($dplayer))
						$this->internalAPI->generatePlayerTeleport($dplayer, $plot, false);
				}else{
					$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
				}
			}
		);
		return true;
	}

	public function getFormClass() : ?string{
		return DenyPlayerForm::class;
	}
}