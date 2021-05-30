<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Label;
use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use pocketmine\form\FormValidationException;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class CloneForm extends ComplexMyPlotForm {

	/** @var Player $player */
	private $player;

	public function __construct(Player $player) {
		$plugin = MyPlot::getInstance();
		$plot = $plugin->getPlotByPosition($player->getPosition());
		if($plot === null) {
			$plot = new \stdClass();
			$plot->X = "";
			$plot->Z = "";
		}
		parent::__construct(
			TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("clone.form")]),
			[
				new Label(
					"0",
					$plugin->getLanguage()->get("clone.formlabel1")
				),
				new Input(
					"1",
					$plugin->getLanguage()->get("clone.formxcoord"),
					"2",
					(string)$plot->X
				),
				new Input(
					"2",
					$plugin->getLanguage()->get("clone.formzcoord"),
					"-4",
					(string)$plot->Z
				),
				new Input(
					"3",
					$plugin->getLanguage()->get("clone.formworld"),
					"world",
					$player->getWorld()->getFolderName()
				),
				new Label(
					"4",
					$plugin->getLanguage()->get("clone.formlabel2")
				),
				new Input(
					"5",
					$plugin->getLanguage()->get("clone.formxcoord"),
					"2"
				),
				new Input(
					"6",
					$plugin->getLanguage()->get("clone.formzcoord"),
					"-4"
				),
				new Input(
					"7",
					$plugin->getLanguage()->get("clone.formworld"),
					"world",
					$player->getWorld()->getFolderName()
				)
			],
			function(Player $player, CustomFormResponse $response) use ($plugin) : void {
				if(is_numeric($response->getString("1")) and is_numeric($response->getString("2")) and is_numeric($response->getString("5")) and is_numeric($response->getString("6"))) {
					$originPlot = MyPlot::getInstance()->getProvider()->getPlot($response->getString("3") === '' ? $this->player->getWorld()->getFolderName() : $response->getString("3"), (int)$response->getString("1"), (int)$response->getString("2"));
					$clonedPlot = MyPlot::getInstance()->getProvider()->getPlot($response->getString("7") === '' ? $this->player->getWorld()->getFolderName() : $response->getString("7"), (int)$response->getString("5"), (int)$response->getString("6"));
				}else
					throw new FormValidationException("Unexpected form data returned");

				if($originPlot->owner !== $player->getName() and !$player->hasPermission("myplot.admin.clone")) {
					$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("notowner"));
					return;
				}
				if($clonedPlot->owner !== $player->getName() and !$player->hasPermission("myplot.admin.clone")) {
					$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("notowner"));
					return;
				}
				if(!$plugin->isLevelLoaded($originPlot->levelName))
					throw new FormValidationException("Invalid world given");
				$plotLevel = $plugin->getLevelSettings($originPlot->levelName);
				$economy = $plugin->getEconomyProvider();
				if($economy !== null and !$economy->reduceMoney($player, $plotLevel->clonePrice)) {
					$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("clone.nomoney"));
					return;
				}
				if($plugin->clonePlot($originPlot, $clonedPlot)) {
					$player->sendMessage($plugin->getLanguage()->translateString("clone.success", [$clonedPlot->__toString(), $originPlot->__toString()]));
				}else{
					$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("error"));
				}
			}
		);
	}
}