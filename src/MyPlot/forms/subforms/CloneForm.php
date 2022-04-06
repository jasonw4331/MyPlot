<?php
declare(strict_types=1);

namespace MyPlot\forms\subforms;

use cosmicpe\form\CustomForm;
use cosmicpe\form\entries\custom\InputEntry;
use cosmicpe\form\entries\custom\LabelEntry;
use MyPlot\forms\MyPlotForm;
use MyPlot\MyPlot;
use MyPlot\plot\BasePlot;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class CloneForm extends CustomForm implements MyPlotForm{
	private BasePlot $clonedPlot;

	public function __construct(Myplot $plugin, Player $player, ?BasePlot $plot){
		if($plot === null)
			throw new \InvalidArgumentException("A plot must be provided");

		$this->clonedPlot = new BasePlot($plot->levelName, $plot->X, $plot->Z);
		parent::__construct(TextFormat::BLACK . $plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("clone.form")]));
		$this->addEntry(new LabelEntry($plugin->getLanguage()->get('clone.formcoordslabel')));
		$this->addEntry(
			new InputEntry($plugin->getLanguage()->get('clone.formxcoord'), '2', (string) $plot->X),
			\Closure::fromCallable(fn(Player $player, InputEntry $entry) => $this->clonedPlot->X = (int) $entry->getValue())
		);
		$this->addEntry(
			new InputEntry($plugin->getLanguage()->get('clone.formzcoord'), '-4', (string) $plot->Z),
			\Closure::fromCallable(fn(Player $player, InputEntry $entry) => $this->clonedPlot->Z = (int) $entry->getValue())
		);
		$this->addEntry(
			new InputEntry($plugin->getLanguage()->get('clone.formworld'), 'world', $plot->levelName),
			\Closure::fromCallable(fn(Player $player, InputEntry $entry) => $this->clonedPlot->levelName = $entry->getValue())
		);
	}
}