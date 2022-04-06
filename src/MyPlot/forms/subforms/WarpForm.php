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

class WarpForm extends CustomForm implements MyPlotForm{

	public function __construct(MyPlot $plugin, Player $player, ?BasePlot $plot){
		parent::__construct(TextFormat::BLACK . $plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("warp.form")]));
		$this->addEntry(
			new InputEntry(
				$plugin->getLanguage()->get("warp.formxcoord"),
				"2"
			),
			\Closure::fromCallable(fn(Player $player, InputEntry $entry) => $plot->X = (int) $entry->getValue())
		);
		$this->addEntry(
			new InputEntry(
				$plugin->getLanguage()->get("warp.formzcoord"),
				"-4"
			),
			\Closure::fromCallable(fn(Player $player, InputEntry $entry) => $plot->Z = (int) $entry->getValue())
		);
		$this->addEntry(
			new InputEntry(
				$plugin->getLanguage()->get("warp.formworld"),
				$player->getWorld()->getFolderName()
			),
			\Closure::fromCallable(fn(Player $player, InputEntry $entry) => $player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name") . " " . $plugin->getLanguage()->get("warp.name") . " $plot->X;$plot->Z {$entry->getValue()}", true))
		);
	}
}