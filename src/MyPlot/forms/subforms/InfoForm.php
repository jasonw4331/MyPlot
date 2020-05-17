<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use MyPlot\subcommand\BiomeSubCommand;
use pocketmine\form\FormValidationException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class InfoForm extends ComplexMyPlotForm {
	public function __construct(Player $player) {
		parent::__construct(null);
		$plugin = MyPlot::getInstance();
		$this->setTitle(TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("info.form")]));

		if(!isset($this->plot))
			$this->plot = $plugin->getPlotByPosition($player);

		$this->addLabel($plugin->getLanguage()->translateString("form.formlabel1", [(string)$this->plot]));
		$this->addInput($plugin->getLanguage()->get("info.formowner"), "owner", $this->plot->owner);
		$this->addInput($plugin->getLanguage()->get("info.formpname"), "name", $this->plot->name);
		$this->addDropdown($plugin->getLanguage()->get("info.formhelpers"),
			array_map(function(string $text) {
				return TextFormat::DARK_BLUE.$text;
			}, $this->plot->helpers)
		);
		$this->addDropdown($plugin->getLanguage()->get("info.formdenied"),
			array_map(function(string $text) {
				return TextFormat::DARK_BLUE.$text;
			}, $this->plot->denied)
		);
		$this->addDropdown($plugin->getLanguage()->get("info.formbiome"),
			array_map(function(string $text) {
				return TextFormat::DARK_BLUE.ucfirst(strtolower(str_replace("_", " ", $text)));
			}, array_keys(BiomeSubCommand::BIOMES)),
			(int)array_search($this->plot->biome, array_keys(BiomeSubCommand::BIOMES))
		);
		$this->addToggle($plugin->getLanguage()->get("info.formpvp"), $this->plot->pvp);

		$this->setCallable(function(Player $player, ?array $data) use ($plugin) {
			if(is_null($data)) {
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name"), true);
				return;
			}
			// TODO: apply changes after submission
		});
	}

	public function processData(&$data) : void {
		if(is_null($data))
			return;
		elseif(is_array($data))
			return; // TODO: data parsing
		else
			throw new FormValidationException("Unexpected form data returned");
	}
}