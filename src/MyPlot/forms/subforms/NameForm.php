<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class NameForm extends ComplexMyPlotForm {
	public function __construct(Player $player) {
		$plugin = MyPlot::getInstance();

		if(!isset($this->plot))
			$this->plot = $plugin->getPlotByPosition($player->getPosition());
		if(!isset($this->plot))
			return;

		parent::__construct(
			TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("name.form")]),
			[
				new Input(
					"0",
					$plugin->getLanguage()->get("name.formtitle"),
					$player->getDisplayName()."'s Plot",
					$this->plot->name
				)
			],
			function(Player $player, CustomFormResponse $response) use ($plugin) : void {
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name")." ".$plugin->getLanguage()->get("name.name").' "'.$response->getString("0").'"', true);
			}
		);
	}
}