<?php
declare(strict_types=1);

namespace MyPlot\forms\subforms;

use cosmicpe\form\entries\simple\Button;
use cosmicpe\form\SimpleForm;
use MyPlot\forms\MyPlotForm;
use MyPlot\MyPlot;
use MyPlot\plot\BasePlot;
use MyPlot\subcommand\BiomeSubCommand;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class BiomeForm extends SimpleForm implements MyPlotForm{
	public function __construct(Myplot $plugin, Player $player, ?BasePlot $plot){
		parent::__construct(TextFormat::BLACK . $plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("biome.form")]));

		$biomes = array_keys(BiomeSubCommand::BIOMES);
		foreach($biomes as $biomeName){
			$this->addButton(
				new Button(TextFormat::DARK_RED . ucfirst(strtolower(str_replace("_", " ", $biomeName)))), // TODO: add images
				\Closure::fromCallable(fn(Player $player, int $entry) => $player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name") . " " . $plugin->getLanguage()->get("biome.name") . ' "' . $biomes[$entry] . '"', true))
			);
		}
	}
}