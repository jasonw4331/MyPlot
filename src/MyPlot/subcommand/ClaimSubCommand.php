<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\subforms\ClaimForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

class ClaimSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.claim");
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
				$name = "";
				if(isset($args[0])){
					$name = $args[0];
				}
				$plot = yield $this->internalAPI->generatePlotByPosition($sender->getPosition());
				if($plot === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
					return;
				}
				if($plot->owner != ""){
					if($plot->owner === $sender->getName()){
						$sender->sendMessage(TextFormat::RED . $this->translateString("claim.yourplot"));
					}else{
						$sender->sendMessage(TextFormat::RED . $this->translateString("claim.alreadyclaimed", [$plot->owner]));
					}
					return;
				}
				$maxPlots = $this->plugin->getMaxPlotsOfPlayer($sender);
				$plotsOfPlayer = 0;
				foreach($this->internalAPI->getAllLevelSettings() as $worldName => $settings){
					$worldName = $this->plugin->getServer()->getWorldManager()->getWorldByName($worldName);
					if($worldName !== null and $worldName->isLoaded()){
						$plotsOfPlayer += count(yield $this->internalAPI->generatePlotsOfPlayer($sender->getName(), $worldName->getFolderName()));
					}
				}
				if($plotsOfPlayer >= $maxPlots){
					$sender->sendMessage(TextFormat::RED . $this->translateString("claim.maxplots", [$maxPlots]));
					return;
				}
				$economy = $this->internalAPI->getEconomyProvider();
				if($economy !== null and !(yield $economy->reduceMoney($sender, $plot->price, 'used plot claim command'))){
					$sender->sendMessage(TextFormat::RED . $this->translateString("claim.nomoney"));
					return;
				}
				if(yield $this->internalAPI->generateClaimPlot($plot, $sender->getName(), $name)){
					$sender->sendMessage($this->translateString("claim.success"));
				}else{
					$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
				}
			}
		);
		return true;
	}

	public function getFormClass() : ?string{
		return ClaimForm::class;
	}
}