<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\element\Label;
use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use MyPlot\subcommand\BiomeSubCommand;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class InfoForm extends ComplexMyPlotForm {
	public function __construct(Player $player) {
		$plugin = MyPlot::getInstance();

		if(!isset($this->plot))
			$this->plot = $plugin->getPlotByPosition($player->getPosition());
		if(!isset($this->plot))
			return;

		parent::__construct(
			TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("info.form")]),
			[
				new Label(
					"0",
					$plugin->getLanguage()->translateString("info.formcoords", [(string)$this->plot])
				),
				new Label(
					"1",
					$plugin->getLanguage()->translateString("info.formowner", [TextFormat::BOLD.$this->plot->owner])
				),
				new Label(
					"2",
					$plugin->getLanguage()->translateString("info.formpname", [TextFormat::BOLD.$this->plot->name])
				),
				new Dropdown(
					"3",
					$plugin->getLanguage()->get("info.formhelpers"),
					count($this->plot->helpers) === 0 ? [TextFormat::DARK_BLUE.$plugin->getLanguage()->get("info.formnohelpers")] : array_map(function(string $text) : string {
						return TextFormat::DARK_BLUE.$text;
					}, $this->plot->helpers)
				),
				new Dropdown(
					"4",
					$plugin->getLanguage()->get("info.formdenied"),
					count($this->plot->denied) === 0 ? [TextFormat::DARK_BLUE.$plugin->getLanguage()->get("info.formnodenied")] : array_map(function(string $text) : string {
						return TextFormat::DARK_BLUE.$text;
					}, $this->plot->denied)
				),
				new Dropdown(
					"5",
					$plugin->getLanguage()->get("info.formbiome"),
					array_map(function(string $text) : string {
						return TextFormat::DARK_BLUE.ucfirst(strtolower(str_replace("_", " ", $text)));
					}, array_keys(BiomeSubCommand::BIOMES)),
					(int)array_search($this->plot->biome, array_keys(BiomeSubCommand::BIOMES), true)
				),
				new Label(
					"6",
					$plugin->getLanguage()->translateString("info.formpvp", [$this->plot->pvp ? "Enabled" : "Disabled"])  // TODO: translations
				)
			],
			function(Player $player, CustomFormResponse $response) : void{}
		);
	}
}