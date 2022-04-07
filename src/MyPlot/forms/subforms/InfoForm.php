<?php
declare(strict_types=1);

namespace MyPlot\forms\subforms;

use cosmicpe\form\CustomForm;
use cosmicpe\form\entries\custom\DropdownEntry;
use cosmicpe\form\entries\custom\LabelEntry;
use MyPlot\forms\MyPlotForm;
use MyPlot\MyPlot;
use MyPlot\plot\BasePlot;
use MyPlot\plot\SinglePlot;
use MyPlot\subcommand\BiomeSubCommand;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class InfoForm extends CustomForm implements MyPlotForm{
	public function __construct(MyPlot $plugin, Player $player, ?BasePlot $plot){
		if(!$plot instanceof SinglePlot)
			throw new \InvalidArgumentException("Plot must be a SinglePlot");

		parent::__construct(TextFormat::BLACK . $plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("info.form")]));

		$this->addEntry(new LabelEntry($plugin->getLanguage()->translateString("info.formcoords", [(string) $plot])));
		$this->addEntry(new LabelEntry($plugin->getLanguage()->translateString("info.formowner", [TextFormat::BOLD . $plot->owner])));
		$this->addEntry(new LabelEntry($plugin->getLanguage()->translateString("info.formpname", [TextFormat::BOLD . $plot->name])));
		$this->addEntry(new DropdownEntry(
			$plugin->getLanguage()->get("info.formhelpers"),
			...(count($plot->helpers) === 0 ?
			[TextFormat::DARK_BLUE . $plugin->getLanguage()->get("info.formnohelpers")] :
			array_map(
				function(string $text) : string{
					return TextFormat::DARK_BLUE . $text;
				},
				$plot->helpers
			))
		));
		$this->addEntry(new DropdownEntry(
			$plugin->getLanguage()->get("info.formdenied"),
			...(count($plot->denied) === 0 ?
			[TextFormat::DARK_BLUE . $plugin->getLanguage()->get("info.formnodenied")] :
			array_map(
				function(string $text) : string{
					return TextFormat::DARK_BLUE . $text;
				},
				$plot->denied
			))
		));
		$this->addEntry(new DropdownEntry(
			$plugin->getLanguage()->get("info.formbiome"),
			...array_map(
			function(string $text) : string{
				return TextFormat::DARK_BLUE . ucfirst(strtolower(str_replace("_", " ", $text)));
			},
			array_keys(BiomeSubCommand::BIOMES)
		)));
		$this->addEntry(new LabelEntry($plugin->getLanguage()->translateString("info.formpvp", [$plot->pvp ? "Enabled" : "Disabled"]))); // TODO: translations
	}
}