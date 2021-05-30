<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use dktapps\pmforms\MenuOption;
use MyPlot\forms\SimpleMyPlotForm;
use MyPlot\MyPlot;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class BiomeForm extends SimpleMyPlotForm {
	/** @var string[] $biomeNames */
	private $biomeNames = [];

	/**
	 * BiomeForm constructor.
	 *
	 * @param string[] $biomes
	 */
	public function __construct(array $biomes) {
		$plugin = MyPlot::getInstance();

		$elements = [];
		$this->biomeNames = $biomes;
		foreach($biomes as $biomeName) {
			$elements[] = new MenuOption(TextFormat::DARK_RED.ucfirst(strtolower(str_replace("_", " ", $biomeName)))); // TODO: add images
		}

		parent::__construct(
			TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("biome.form")]),
			"",
			$elements,
			function(Player $player, int $selectedOption) use ($plugin) : void {
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name")." ".$plugin->getLanguage()->get("biome.name").' "'.$this->biomeNames[$selectedOption].'"', true);
			}
		);
	}
}