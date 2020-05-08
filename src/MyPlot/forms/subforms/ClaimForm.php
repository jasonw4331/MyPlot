<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\form\FormValidationException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ClaimForm extends ComplexMyPlotForm {

	/** @var Player $player */
	private $player;

	public function __construct(Player $player) {
		parent::__construct(null);
		$plugin = MyPlot::getInstance();
		$this->setTitle($plugin->getLanguage()->translateString("form.header", [TextFormat::DARK_BLUE."Claim Form"]));

		$this->addInput("Plot X Coordinate", "2");
		$this->addInput("Plot Z Coordinate", "-4");
		$this->addInput("Plot World", "world", $player->getLevel()->getFolderName());

		$this->setCallable(function(Player $player, ?Plot $data) use ($plugin) {
			if(is_null($data)) {
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name"), true);
				return;
			}
			if($data->owner != "") {
				if($data->owner === $player->getName()) {
					$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("claim.yourplot"));
				}else{
					$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("claim.alreadyclaimed", [$data->owner]));
				}
				return;
			}
			$maxPlots = $plugin->getMaxPlotsOfPlayer($player);
			$plotsOfPlayer = 0;
			foreach($plugin->getPlotLevels() as $level => $settings) {
				$level = $plugin->getServer()->getLevelByName((string)$level);
				if(!$level->isClosed()) {
					$plotsOfPlayer += count($plugin->getPlotsOfPlayer($player->getName(), $level->getFolderName()));
				}
			}
			if($plotsOfPlayer >= $maxPlots) {
				$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("claim.maxplots", [$maxPlots]));
				return;
			}
			$plotLevel = $plugin->getLevelSettings($data->levelName);
			$economy = $plugin->getEconomyProvider();
			if($economy !== null and !$economy->reduceMoney($player, $plotLevel->claimPrice)) {
				$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("claim.nomoney"));
				return;
			}
			if($plugin->claimPlot($data, $player->getName())) {
				$player->sendMessage($plugin->getLanguage()->translateString("claim.success"));
			}else{
				$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("error"));
			}
		});
	}

	public function handleResponse(Player $player, $data) : void {
		$this->player = $player;
		parent::handleResponse($player, $data);
	}

	public function processData(&$data) : void {
		if(is_null($data))
			return;
		elseif(is_array($data) and is_numeric($data[0]) and is_numeric($data[1]))
			$data = MyPlot::getInstance()->getProvider()->getPlot(empty($data[2]) ? $this->player->getLevel()->getFolderName() : $data[2], (int)$data[0], (int)$data[1]);
		elseif(is_array($data) and empty($data[0]) and empty($data[1])) {
			$plot = MyPlot::getInstance()->getPlotByPosition($this->player);
			if($plot === null) {
				$this->player->sendForm(new self($this->player));
				throw new FormValidationException("Unexpected form data returned");
			}
			$data = $plot;
		}else
			throw new FormValidationException("Unexpected form data returned");
	}
}