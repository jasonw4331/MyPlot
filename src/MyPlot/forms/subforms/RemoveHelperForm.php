<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class RemoveHelperForm extends ComplexMyPlotForm {
	public function __construct() {
		$plugin = MyPlot::getInstance();
		parent::__construct(
			TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("removehelper.form")]),
			[
				new Dropdown(
					"0",
					$plugin->getLanguage()->get("removehelper.dropdown"),
					empty($this->plot->helpers) ? [TextFormat::DARK_BLUE.$plugin->getLanguage()->get("removehelper.formnohelpers")] : array_map(function(string $text) {
						return TextFormat::DARK_BLUE.$text;
					}, $this->plot->helpers)
				)
			],
			function(Player $player, CustomFormResponse $response) use ($plugin) : void {
				if(empty($this->plot->helpers))
					return;
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name")." ".$plugin->getLanguage()->get("removehelper.name").' "'.$this->plot->helpers[$response->getInt("0")].'"', true);
			}
		);
	}
}