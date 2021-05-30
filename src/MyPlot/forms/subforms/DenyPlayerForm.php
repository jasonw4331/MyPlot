<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class DenyPlayerForm extends ComplexMyPlotForm {
	/** @var string[] $players */
	private $players = [];

	public function __construct(Plot $plot) {
		$plugin = MyPlot::getInstance();
		$players = [];
		if(!in_array("*", $plot->denied, true)) {
			$players = ["*"];
			$this->players = ["*"];
		}
		foreach($plugin->getServer()->getOnlinePlayers() as $player) {
			$players[] = $player->getDisplayName();
			$this->players[] = $player->getName();
		}
		parent::__construct(
			TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("denyplayer.form")]),
			[
				new Dropdown(
					"0",
					$plugin->getLanguage()->get("denyplayer.dropdown"),
					array_map(function(string $text) : string {
						return TextFormat::DARK_BLUE.$text;
					}, $players)
				)
			],
			function(Player $player, CustomFormResponse $response) use ($plugin) : void {
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name")." ".$plugin->getLanguage()->get("denyplayer.name").' "'.$this->players[$response->getInt("0")].'"', true);
			}
		);
	}
}