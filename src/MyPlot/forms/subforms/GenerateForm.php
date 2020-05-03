<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;


use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
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
				$this->addSlider($key, 1, 4 * $value, -1, $value);
			}elseif(is_bool($value)) {
				$this->addToggle($key, $value);
			}elseif(is_string($value)) {
				$this->addInput($key, "", $value);
			}
			$this->keys[] = $key;
		}

		$this->setCallable(function(Player $player, ?array $data) use ($plugin) {
			if(is_null($data)) {
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name"), true);
				return;
			}
			$plugin->generateLevel(array_shift($data), array_shift($data), $data);
		});
	}

	public function processData(&$data) : void {
		if(is_null($data))
			return;
		var_dump($data);
		//elseif(is_array($data))
		//	$data = $this->keys[$data[0]];
		//else
		//	throw new FormValidationException("Unexpected form data returned");
	}
}