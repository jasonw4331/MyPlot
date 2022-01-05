<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use dktapps\pmforms\MenuOption;
use MyPlot\forms\SimpleMyPlotForm;
use MyPlot\MyPlot;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class HomeForm extends SimpleMyPlotForm {
	public function __construct(Player $player) {
		$plugin = MyPlot::getInstance();

		$plots = $plugin->getPlotsOfPlayer($player->getName(), $player->getWorld()->getFolderName());
		$i = 1;
		$elements = [];
		foreach($plots as $plot) {
			$elements[] = new MenuOption(TextFormat::DARK_RED.$i++.") ".$plot->name." ".(string)$plot);
		}
		parent::__construct(
			TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("home.form")]),
			"",
			$elements,
			function(Player $player, int $selectedOption) use ($plugin) : void {
				// TODO: merge list and home into one form
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name")." ".$plugin->getLanguage()->get("home.name").' "'.($selectedOption+1).'"', true);
			}
		);
	}
}