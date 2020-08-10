<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use pocketmine\Player;
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
					$this->plot ? array_map(function(string $text) {
						return TextFormat::DARK_BLUE.$text;
					}, $this->plot->denied) : []
				)
			],
			function(Player $player, CustomFormResponse $response) use ($plugin) : void {
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name")." ".$plugin->getLanguage()->get("undenyplayer.name").' "'.$this->plot->denied[$response->getInt("0")].'"', true);
			}
		);
	}
}