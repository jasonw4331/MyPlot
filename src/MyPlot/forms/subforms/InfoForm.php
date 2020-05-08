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
		$this->setTitle($plugin->getLanguage()->translateString("form.header", [TextFormat::DARK_BLUE."Info Form"]));

		if(!isset($this->plot))
			$this->plot = $plugin->getPlotByPosition($player);

		$this->addLabel((string)$this->plot);
		$this->addInput("Owner", "owner", $this->plot->owner);
		$this->addInput("Plot Name", "name", $this->plot->name);
		$this->addDropdown("Helpers", $this->plot->helpers);
		$this->addDropdown("Denied", $this->plot->denied);
		$this->addDropdown("Biome", array_keys(BiomeSubCommand::BIOMES), (int)array_search($this->plot->biome, array_keys(BiomeSubCommand::BIOMES)));
		$this->addToggle("PvP", $this->plot->pvp);

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