<?php
declare(strict_types=1);

namespace MyPlot\forms\subforms;

use cosmicpe\form\entries\simple\Button;
use cosmicpe\form\SimpleForm;
use MyPlot\forms\MyPlotForm;
use MyPlot\MyPlot;
use MyPlot\plot\BasePlot;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class HomeForm extends SimpleForm implements MyPlotForm{
	public function __construct(MyPlot $plugin, Player $player, ?BasePlot $plot){
		parent::__construct(TextFormat::BLACK . $plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("home.form")]));
		// TODO: merge list and home into one form
		$i = 0;
		foreach($plugin->getPlotsOfPlayer($player->getName(), $player->getWorld()->getFolderName()) as $plot)
			$this->addButton(new Button(TextFormat::DARK_RED . ++$i . ") " . $plot->name . " " . $plot), \Closure::fromCallable(fn(Player $player, int $data) => $plugin->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name") . " " . $plugin->getLanguage()->get("home.name") . ' "' . ($data + 1) . '"', true)));
	}
}