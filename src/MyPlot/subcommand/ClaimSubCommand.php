<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\ClaimForm;
use MyPlot\MyPlot;
use pocketmine\block\Block;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;

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
		$plot = $this->getPlugin()->getPlotByPosition($sender);
		if($plot === null) {
			$sender->sendMessage(MyPlot::PREFIX . C::RED . "Du stehst auf keinem Grundstück!");
			return true;
		}
		if($plot->owner != "") {
			if($plot->owner === $sender->getName()) {
                $sender->sendMessage(MyPlot::PREFIX . C::RED."Dir gehört dieses Grundstück bereits!");
			}else{
                $sender->sendMessage(MyPlot::PREFIX . C::RED . "Dieses Grundstück hat schon einen Besitzer");
			}
			return true;
		}
		$maxPlots = $this->getPlugin()->getMaxPlotsOfPlayer($sender);
		$plotsOfPlayer = 0;
		foreach($this->getPlugin()->getPlotLevels() as $level => $settings) {
			$level = $this->getPlugin()->getServer()->getLevelByName((string)$level);
			if($level !== null and !$level->isClosed()) {
				$plotsOfPlayer += count($this->getPlugin()->getPlotsOfPlayer($sender->getName(), $level->getFolderName()));
			}
		}
		if($plotsOfPlayer >= $maxPlots) {
            $sender->sendMessage(MyPlot::PREFIX . C::RED . "Du hast die Maximalanzahl von ".C::YELLOW.$maxPlots." Grundstücken " . C::RED."erreicht.");
            $sender->sendMessage(MyPlot::PREFIX . C::RED . "Du kannst mit einem Rang auf ".C::YELLOW."shop.EntenGames.de". C::RED." mehr Grundstücke besitzen.");
            return true;
		}
		$economy = $this->getPlugin()->getEconomyProvider();
		if($economy !== null and !$economy->reduceMoney($sender, $plot->price)) {
			$sender->sendMessage(C::RED . $this->translateString("claim.nomoney"));
			return true;
		}
		if($this->getPlugin()->claimPlot($plot, $sender->getName(), $name)) {
            //$this->getPlugin()->setRand($plot, Block::get(Block::STONE_SLAB, 1));
            $sender->sendMessage(MyPlot::PREFIX.C::GREEN."Du hast das Grundstück für dich beansprucht.");
		}else{
			$sender->sendMessage(C::RED . $this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		if($player !== null and MyPlot::getInstance()->isLevelLoaded($player->getLevelNonNull()->getFolderName()))
			return new ClaimForm($player);
		return null;
	}
}