<?php
declare(strict_types=1);

namespace MyPlot\forms\subforms;

use cosmicpe\form\CustomForm;
use cosmicpe\form\entries\custom\DropdownEntry;
use MyPlot\forms\MyPlotForm;
use MyPlot\MyPlot;
use MyPlot\plot\BasePlot;
use MyPlot\plot\SinglePlot;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class UndenyPlayerForm extends CustomForm implements MyPlotForm{
	public function __construct(MyPlot $plugin, Player $player, ?BasePlot $plot){
		if(!$plot instanceof SinglePlot)
			throw new \InvalidArgumentException("Plot must be a SinglePlot");

		parent::__construct(TextFormat::BLACK . $plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("undenyplayer.form")]));
		$this->addEntry(
			new DropdownEntry(
				$plugin->getLanguage()->get("undenyplayer.dropdown"),
				...(count($plot->denied) < 1 ? [TextFormat::DARK_BLUE . $plugin->getLanguage()->get("undenyplayer.formnodenied")] : array_map(function(string $text){
				return TextFormat::DARK_BLUE . $text;
			}, $plot->denied))
			),
			\Closure::fromCallable(fn(Player $player, DropdownEntry $entry) => $plugin->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name") . " " . $plugin->getLanguage()->get("undenyplayer.name") . ' "' . $entry->getValue() . '"', true))
		);
	}
}