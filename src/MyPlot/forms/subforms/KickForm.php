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

class KickForm extends CustomForm implements MyPlotForm{

	public function __construct(MyPlot $plugin, Player $player, ?BasePlot $plot){
		parent::__construct(TextFormat::BLACK . $plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("kick.form")]));
		$this->addEntry(
			new DropdownEntry(
				$plugin->getLanguage()->get("kick.dropdown"),
				...array_map(
					fn(Player $player2) => $player2->getDisplayName(),
					array_filter(
						array_filter(
							$plugin->getServer()->getOnlinePlayers(),
							fn(Player $player2) => $player2->getWorld()->getFolderName() === $plot->levelName
						),
						fn(Player $player2) => $plugin->getPlotFast($player2->getPosition()->x, $player2->getPosition()->z, $plugin->getLevelSettings($player2->getWorld()->getFolderName()))?->isSame($plot)
					)
				)
			),
			\Closure::fromCallable(fn(Player $player, DropdownEntry $entry) => $plugin->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name") . " " . $plugin->getLanguage()->get("kick.name") . ' "' . $entry->getValue() . '"', true))
		);
	}
}