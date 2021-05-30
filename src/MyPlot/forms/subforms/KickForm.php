<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class KickForm extends ComplexMyPlotForm {
	/** @var string[] $players */
	private $players = [];

	public function __construct() {
		$plugin = MyPlot::getInstance();
		$players = [];
		foreach($plugin->getServer()->getOnlinePlayers() as $player) {
			$plot = $plugin->getPlotByPosition($player->getPosition());
			if($plot === null)
				continue;
			if($this->plot !== null and !$plot->isSame($this->plot))
				continue;
			$players[] = $player->getDisplayName();
			$this->players[] = $player->getName();
		}
		parent::__construct(
			TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("kick.form")]),
			[
				new Dropdown(
					"0",
					$plugin->getLanguage()->get("kick.dropdown"),
					$players
				)
			],
			function(Player $player, CustomFormResponse $response) use ($plugin) : void {
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name")." ".$plugin->getLanguage()->get("kick.name").' "'.$this->players[$response->getInt("0")].'"', true);
			}
		);
	}
}