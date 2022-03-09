<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

class DisposeSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.dispose");
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
				$plot = yield $this->internalAPI->generatePlotByPosition($sender->getPosition());
				if($plot === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
					return;
				}
				if($plot->owner !== $sender->getName() and !$sender->hasPermission("myplot.admin.dispose")){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
					return;
				}
				if(!isset($args[0]) or $args[0] !== $this->translateString("confirm")){
					$plotId = TextFormat::GREEN . $plot . TextFormat::WHITE;
					$sender->sendMessage($this->translateString("dispose.confirm", [$plotId]));
					return;
				}
				$economy = $this->internalAPI->getEconomyProvider();
				$price = $this->plugin->getLevelSettings($plot->levelName)->disposePrice;
				if($economy !== null and !(yield $economy->reduceMoney($sender, $price))){
					$sender->sendMessage(TextFormat::RED . $this->translateString("dispose.nomoney"));
					return;
				}
				if(yield $this->plugin->disposePlot($plot)){
					$sender->sendMessage(TextFormat::GREEN . $this->translateString("dispose.success"));
				}else{
					$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
				}
			}
		);
		return true;
	}
}