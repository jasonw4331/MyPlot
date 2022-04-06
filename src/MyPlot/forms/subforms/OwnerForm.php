<?php
declare(strict_types=1);

namespace MyPlot\forms\subforms;

use cosmicpe\form\CustomForm;
use cosmicpe\form\entries\custom\DropdownEntry;
use MyPlot\forms\MyPlotForm;
use MyPlot\MyPlot;
use MyPlot\plot\BasePlot;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class OwnerForm extends CustomForm implements MyPlotForm{

	public function __construct(MyPlot $plugin, Player $player, ?BasePlot $plot){
		parent::__construct(TextFormat::BLACK . $plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("setowner.form")]));
		$this->addEntry(
			new DropdownEntry(
				$plugin->getLanguage()->get("setowner.dropdown"),
				...array_map(
					fn(Player $player) => $player->getDisplayName(),
					$plugin->getServer()->getOnlinePlayers()
				)
			),
			\Closure::fromCallable(fn(Player $player, DropdownEntry $entry) => $plugin->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name") . " " . $plugin->getLanguage()->get("setowner.name") . ' "' . $entry->getValue() . '"', true))
		);
	}
}