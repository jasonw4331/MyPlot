<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class FillForm extends ComplexMyPlotForm {
	public function __construct() {
		$plugin = MyPlot::getInstance();

		parent::__construct(
			TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("fill.form")]),
			[
				new Input(
					"0",
					$plugin->getLanguage()->get("fill.formtitle"),
					"1:0",
					"1:0"
				)
			],
			function(Player $player, CustomFormResponse $response) use ($plugin) : void {
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name")." ".$plugin->getLanguage()->get("fill.name").' "'.$response->getString("0").'"', true);
			}
		);
	}
}