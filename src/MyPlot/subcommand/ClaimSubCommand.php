<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ClaimSubCommand extends SubCommand
{
	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
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
		$player = $sender->getServer()->getPlayer($sender->getName());
		$plot = $this->getPlugin()->getPlotByPosition($player);
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
		$maxPlots = $this->getPlugin()->getMaxPlotsOfPlayer($player);
		$plotsOfPlayer = 0;
		foreach($this->getPlugin()->getPlotLevels() as $level => $settings) {
			$level = $this->getPlugin()->getServer()->getLevelByName((string)$level);
			if(!$level->isClosed()) {
				$plotsOfPlayer += count($this->getPlugin()->getPlotsOfPlayer($player->getName(), $level->getFolderName()));
			}
		}
		if($plotsOfPlayer >= $maxPlots) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("claim.maxplots", [$maxPlots]));
			return true;
		}
		$plotLevel = $this->getPlugin()->getLevelSettings($plot->levelName);
		$economy = $this->getPlugin()->getEconomyProvider();
		if($economy !== null and !$economy->reduceMoney($player, $plotLevel->claimPrice)) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("claim.nomoney"));
			return true;
		}
		$plot->owner = $sender->getName();
		$plot->name = $name;
		if($this->getPlugin()->savePlot($plot)) {
			$sender->sendMessage($this->translateString("claim.success"));
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}
}