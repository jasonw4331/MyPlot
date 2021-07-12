<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\ClaimForm;
use MyPlot\MyPlot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

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
	public function execute(CommandSender $sender, array $args) : bool {
		$name = "";
		if(isset($args[0])) {
			$name = $args[0];
		}
		$plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if($plot->owner != "") {
			if($plot->owner === $sender->getName()) {
				$sender->sendMessage(TextFormat::RED . $this->translateString("claim.yourplot"));
			}else{
				$sender->sendMessage(TextFormat::RED . $this->translateString("claim.alreadyclaimed", [$plot->owner]));
			}
			return true;
		}
		$maxPlots = $this->getPlugin()->getMaxPlotsOfPlayer($sender);
		$plotsOfPlayer = 0;
		foreach($this->getPlugin()->getPlotlevels() as $level => $settings) {
			$level = $this->getPlugin()->getServer()->getWorldManager()->getWorldByName((string)$level);
			if($level !== null and $level->isLoaded()) {
				$plotsOfPlayer += count($this->getPlugin()->getPlotsOfPlayer($sender->getName(), $level->getFolderName()));
			}
		}
		if($plotsOfPlayer >= $maxPlots) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("claim.maxplots", [$maxPlots]));
			return true;
		}
		$economy = $this->getPlugin()->getEconomyProvider();
		if($economy !== null and !$economy->reduceMoney($sender, $plot->price)) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("claim.nomoney"));
			return true;
		}
		if($this->getPlugin()->claimPlot($plot, $sender->getName(), $name)) {
			$sender->sendMessage($this->translateString("claim.success"));
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($player !== null and MyPlot::getInstance()->isLevelLoaded($player->getWorld()->getFolderName()))
			return new ClaimForm($player);
		return null;
	}
}