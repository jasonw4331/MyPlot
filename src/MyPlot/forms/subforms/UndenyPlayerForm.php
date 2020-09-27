<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class UndenyPlayerForm extends ComplexMyPlotForm {
	public function __construct() {
		$plugin = MyPlot::getInstance();
		parent::__construct(
			TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("undenyplayer.form")]),
			[
				new Dropdown(
					"0",
					$plugin->getLanguage()->get("undenyplayer.dropdown"),
					empty($this->plot->denied) ? [TextFormat::DARK_BLUE.$plugin->getLanguage()->get("undenyplayer.formnodenied")] : array_map(function(string $text) {
						return TextFormat::DARK_BLUE.$text;
					}, $this->plot->denied)
				)
			],
			function(Player $player, CustomFormResponse $response) use ($plugin) : void {
				if(empty($this->plot->denied))
					return;
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name")." ".$plugin->getLanguage()->get("undenyplayer.name").' "'.$this->plot->denied[$response->getInt("0")].'"', true);
			}
		);
	}
}