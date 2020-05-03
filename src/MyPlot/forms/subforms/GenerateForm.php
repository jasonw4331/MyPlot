<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;


use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\form\FormValidationException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class GenerateForm extends ComplexMyPlotForm {
	/** @var string[] $players */
	private $keys = [];

	public function __construct() {
		parent::__construct(null);
		$plugin = MyPlot::getInstance();
		$this->setTitle($plugin->getLanguage()->translateString("form.header", [TextFormat::DARK_BLUE."Generate Form"]));

		$this->addInput("World Name", "plots");
		$this->addInput("World Generator", "", "myplot");

		foreach($plugin->getConfig()->get("DefaultWorld", []) as $key => $value) {
			if(is_numeric($value)) {
				if($value > 0)
					$this->addSlider($key, 1, 4 * $value, -1, $value);
				else
					$this->addSlider($key, 1, 1000, -1, $value);
			}elseif(is_bool($value)) {
				$this->addToggle($key, $value);
			}elseif(is_string($value)) {
				$this->addInput($key, "", $value);
			}
			$this->keys[] = $key;
		}

		$this->keys[] = "teleport"; // added option to end of keys for data parsing
		$this->addToggle("Teleport After Generated", true);

		$this->setCallable(function(Player $player, ?array $data) use ($plugin) {
			if(is_null($data)) {
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name"), true);
				return;
			}
			$teleport = array_pop($data);
			$world = array_shift($data);
			$plugin->generateLevel($world, array_shift($data), $data);
			if($teleport)
				$plugin->teleportPlayerToPlot($player, new Plot($world, 0, 0));
		});
	}

	public function processData(&$data) : void {
		if(is_null($data))
			return;
		elseif(is_array($data)) {
			$copy = [];
			foreach($data as $key => $value) {
				if(isset($this->keys[$key-2]))
					$copy[$this->keys[$key-2]] = $value;
				else
					$copy[] = $value;
			}
			$data = $copy;
		}else
			throw new FormValidationException("Unexpected form data returned");
	}
}