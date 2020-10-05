<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use pocketmine\form\FormValidationException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ClaimForm extends ComplexMyPlotForm {

	/** @var Player $player */
	private $player;

	public function __construct(Player $player) {
		$plugin = MyPlot::getInstance();
		$plot = $plugin->getPlotByPosition($player);
		if($plot === null) {
			$plot = new \stdClass();
			$plot->X = "";
			$plot->Z = "";
		}
		parent::__construct(
			TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("claim.form")]),
			[
				new Input(
					"0",
					$plugin->getLanguage()->get("claim.formxcoord"),
					"2",
					(string)$plot->X
				),
				new Input(
					"1",
					$plugin->getLanguage()->get("claim.formzcoord"),
					"2",
					(string)$plot->Z
				),
				new Input(
					"2",
					$plugin->getLanguage()->get("claim.formworld"),
					"world",
					$player->getLevel()->getFolderName()
				)
			],
			function(Player $player, CustomFormResponse $response) use ($plugin) : void {
				if(is_numeric($response->getString("0")) and is_numeric($response->getString("1")))
					$data = MyPlot::getInstance()->getProvider()->getPlot(
						empty($response->getString("2")) ? $this->player->getLevel()->getFolderName() : $response->getString("2"),
						(int)$response->getString("0"),
						(int)$response->getString("1")
					);
				elseif(empty($response->getString("0")) or empty($response->getString("1"))) {
					$plot = MyPlot::getInstance()->getPlotByPosition($this->player);
					if($plot === null) {
						$this->player->sendForm(new self($this->player));
						throw new FormValidationException("Unexpected form data returned");
					}
					$data = $plot;
				}else {
					throw new FormValidationException("Unexpected form data returned");
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
				$economy = $plugin->getEconomyProvider();
				if($economy !== null and !$economy->reduceMoney($player, $data->price)) {
					$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("claim.nomoney"));
					return;
				}
				if($plugin->claimPlot($data, $player->getName())) {
					$player->sendMessage($plugin->getLanguage()->translateString("claim.success"));
				}else{
					$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("error"));
				}
			}
		);
	}
}