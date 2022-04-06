<?php
declare(strict_types=1);

namespace MyPlot\forms\subforms;

use cosmicpe\form\CustomForm;
use cosmicpe\form\entries\custom\InputEntry;
use cosmicpe\form\entries\custom\SliderEntry;
use cosmicpe\form\entries\custom\ToggleEntry;
use MyPlot\forms\MyPlotForm;
use MyPlot\MyPlot;
use MyPlot\plot\BasePlot;
use pocketmine\item\ItemBlock;
use pocketmine\item\StringToItemParser;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class GenerateForm extends CustomForm implements MyPlotForm{
	public function __construct(MyPlot $plugin, Player $player, ?BasePlot $plot){
		parent::__construct(TextFormat::BLACK . $plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("generate.form")]));

		static $outputs = [];
		$this->addEntry(
			new InputEntry($plugin->getLanguage()->get("generate.formworld"), 'plots'),
			\Closure::fromCallable(fn(Player $player, InputEntry $entry) => $outputs[] = $entry->getValue())
		);
		$this->addEntry(
			new InputEntry($plugin->getLanguage()->get("generate.formgenerator"), '', 'myplot'),
			\Closure::fromCallable(fn(Player $player, InputEntry $entry) => $outputs[] = $entry->getValue())
		);

		foreach($plugin->getConfig()->get("DefaultWorld", []) as $key => $value){
			if(is_numeric($value)){
				if($value > 0)
					$this->addEntry(
						new SliderEntry($key, 1, 4 * $value, 1, $value),
						\Closure::fromCallable(fn(Player $player, SliderEntry $entry) => $outputs[] = $entry->getValue())
					);
				else
					$this->addEntry(
						new SliderEntry($key, 1, 1000, 1, 1),
						\Closure::fromCallable(fn(Player $player, SliderEntry $entry) => $outputs[] = $entry->getValue())
					);
			}elseif(is_bool($value)){
				$this->addEntry(
					new ToggleEntry($key, $value),
					\Closure::fromCallable(fn(Player $player, ToggleEntry $entry) => $outputs[] = $entry->getValue())
				);
			}elseif(is_string($value)){
				$this->addEntry(
					new InputEntry($key, '', $value),
					\Closure::fromCallable(fn(Player $player, InputEntry $entry) => $outputs[] = $entry->getValue())
				);
			}
		}

		$this->addEntry(
			new ToggleEntry($plugin->getLanguage()->get("generate.formteleport"), true),
			\Closure::fromCallable(function(Player $player, ToggleEntry $entry) use ($plugin, $outputs){
				$worldName = array_shift($outputs);
				if($player->getServer()->getWorldManager()->isWorldGenerated($worldName)){
					$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("generate.exists", [$worldName]));
					return;
				}

				$blockNames = array_slice($outputs, -6, 5, true); // TODO: UPDATE WHEN CONFIG IS UPDATED
				$blockNames = array_map(fn(string $blockName) => str_replace(' ', '_', $blockName), $blockNames);
				$blockNames = array_filter($blockNames, fn(string $blockName) => StringToItemParser::getInstance()->parse($blockName) instanceof ItemBlock);
				$outputs = array_merge($outputs, $blockNames);

				if($plugin->generateLevel($worldName, array_shift($outputs), $outputs)){
					if($entry->getValue() === true)
						$plugin->teleportPlayerToPlot($player, new BasePlot($worldName, 0, 0));
					$player->sendMessage($plugin->getLanguage()->translateString("generate.success", [$worldName]));
				}else{
					$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("generate.error"));
				}
			})
		);
	}
}