<?php
declare(strict_types=1);

namespace MyPlot\forms\subforms;

use cosmicpe\form\CustomForm;
use cosmicpe\form\entries\custom\InputEntry;
use MyPlot\forms\MyPlotForm;
use MyPlot\MyPlot;
use MyPlot\plot\BasePlot;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class ClaimForm extends CustomForm implements MyPlotForm{
	public function __construct(Myplot $plugin, Player $player, ?BasePlot $plot){
		if($plot === null)
			throw new \InvalidArgumentException("Plot must be a SinglePlot");

		parent::__construct(TextFormat::BLACK . $plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("claim.form")]));
		$this->addEntry(
			new InputEntry(
				$plugin->getLanguage()->get("claim.formxcoord"),
				'2',
				(string) $plot->X
			),
			\Closure::fromCallable(fn(Player $player, InputEntry $entry) => $plot->X = (int) $entry->getValue())
		);
		$this->addEntry(
			new InputEntry(
				$plugin->getLanguage()->get("claim.formzcoord"),
				'2',
				(string) $plot->Z
			),
			\Closure::fromCallable(fn(Player $player, InputEntry $entry) => $plot->Z = (int) $entry->getValue())
		);
		$this->addEntry(
			new InputEntry($plugin->getLanguage()->get("claim.formname")),
			\Closure::fromCallable(fn(Player $player, InputEntry $entry) => $player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name") . ' ' . $plugin->getLanguage()->get("claim.name") . ' ' . $plot->X . ';' . $plot->Z . ($entry->getValue() === '' ?: ' ' . $entry->getValue()), true))
		);
	}
}