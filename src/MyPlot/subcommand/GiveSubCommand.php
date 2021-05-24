<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\GiveForm;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\block\Block;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;

class GiveSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.give");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(count($args) === 0) {
            $sender->sendMessage(C::RED."/p give <Spieler>");
            return true;
		}
		$newOwner = $args[0];
		$plot = $this->getPlugin()->getPlotByPosition($sender);
		if($plot === null) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du stehst auf keinem Grundstück!");
			return true;
		}
		if($plot->owner !== $sender->getName()) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du bist nicht Besitzer dieses Grundstücks!");
			return true;
		}
		$newOwner = $this->getPlugin()->getServer()->getPlayer($newOwner);
		if(!$newOwner instanceof Player) {
            $sender->sendMessage(MyPlot::PREFIX . C::RED . "Der Spieler muss online sein, damit du ihm dein Grundstück geben kannst!");
			return true;
		}elseif($newOwner->getName() === $sender->getName()) {
            $sender->sendMessage(MyPlot::PREFIX . C::RED . "Du kannst dir nicht selber das Grundstück geben!");
			return true;
		}
		$maxPlots = $this->getPlugin()->getMaxPlotsOfPlayer($newOwner);
		$plotsOfPlayer = 0;
		foreach($this->getPlugin()->getPlotLevels() as $level => $settings) {
			$level = $this->getPlugin()->getServer()->getLevelByName((string)$level);
			if($level !== null and !$level->isClosed()) {
				$plotsOfPlayer += count($this->getPlugin()->getPlotsOfPlayer($newOwner->getName(), $level->getFolderName()));
			}
		}
		if($plotsOfPlayer >= $maxPlots) {
            $sender->sendMessage(MyPlot::PREFIX.C::RED."Der Spieler hat schon die maximale Anzahl an Grundstücken!");
			return true;
		}
		if(count($args) == 2 and $args[1] == $this->translateString("confirm")) {
			if($this->getPlugin()->claimPlot($plot, $newOwner->getName())) {
                //$this->getPlugin()->setWand($plot, Block::get(Block::DIRT));
                //$this->getPlugin()->setRand($plot, Block::get(Block::STONE_SLAB, 1));
                $sender->sendMessage(MyPlot::PREFIX.C::GREEN."Du hast ".C::YELLOW.$newOwner->getName().C::GREEN." dein Grundstück gegeben");
                $newOwner->sendMessage(MyPlot::PREFIX.C::YELLOW.$sender->getName().C::GREEN." hat dir sein Grundstück mit der ID ".C::YELLOW.$plot.C::GREEN." gegeben.");
            }else{
				$sender->sendMessage(C::RED . $this->translateString("error"));
			}
		}else{
            $sender->sendMessage(MyPlot::PREFIX.C::RED."Bitte gebe ".C::YELLOW."/plot give ".$newOwner->getName()." confirm".C::RED." ein, um zu bestätigen, dass dein Grundstück an ".C::YELLOW.$newOwner->getName().C::RED." gegeben wird.");
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($player !== null and $this->getPlugin()->getPlotByPosition($player) instanceof Plot)
			return new GiveForm();
		return null;
	}
}