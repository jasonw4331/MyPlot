<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\block\BlockIds;
use pocketmine\form\FormValidationException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class GenerateForm extends ComplexMyPlotForm {
	/** @var string[] $keys */
	private $keys = [];

	public function __construct() {
		parent::__construct(null);
		$plugin = MyPlot::getInstance();
		$this->setTitle(TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("generate.form")]));

		$this->addInput($plugin->getLanguage()->get("generate.formworld"), "plots");
		$this->addInput($plugin->getLanguage()->get("generate.formgenerator"), "", "myplot");

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
		// TODO: multi lang for teleport
		$this->keys[] = "teleport"; // added option to end of keys for data parsing
		$this->addToggle($plugin->getLanguage()->get("generate.formteleport"), true);

		$this->setCallable(function(Player $player, ?array $data) use ($plugin) {
			if(is_null($data)) {
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name"), true);
				return;
			}
			$world = array_shift($data);
			if($player->getServer()->isLevelGenerated($world)) {
				$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("generate.exists", [$world]));
				return;
			}
			$teleport = array_pop($data);
			$data = array_map(function($val) {
				if(strpos($val, ':') !== false) {
					$peices = explode(':', $val);
					if(defined(BlockIds::class."::".strtoupper(str_replace(' ', '_', $peices[0]))))
						return constant(BlockIds::class."::".strtoupper(str_replace(' ', '_', $val))).':'.($peices[1] ?? 0);
					return $val;
				}elseif(is_numeric($val))
					return $val.':0';
				elseif(defined(BlockIds::class."::".strtoupper(str_replace(' ', '_', $val))))
					return constant(BlockIds::class."::".strtoupper(str_replace(' ', '_', $val))).':0';
				return $val;
			}, $data);
			if($plugin->generateLevel($world, array_shift($data), $data)) {
				if($teleport)
					$plugin->teleportPlayerToPlot($player, new Plot($world, 0, 0));
				$player->sendMessage($plugin->getLanguage()->translateString("generate.success", [$world]));
			}else{
				$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("generate.error"));
			}
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