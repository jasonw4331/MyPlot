<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Slider;
use dktapps\pmforms\element\Toggle;
use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\block\BlockLegacyIds;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class GenerateForm extends ComplexMyPlotForm {
	/** @var string[] $keys */
	private $keys = [];

	public function __construct() {
		$plugin = MyPlot::getInstance();
		$elements = [
			new Input(
				"0",
				$plugin->getLanguage()->get("generate.formworld"),
				"plots"
			),
			new Input(
				"1",
				$plugin->getLanguage()->get("generate.formgenerator"),
				"",
				"myplot"
			)
		];
		$i = 2;
		foreach($plugin->getConfig()->get("DefaultWorld", []) as $key => $value) {
			if(is_numeric($value)) {
				if($value > 0)
					$elements[] = new Slider("$i", $key, 1, 4 * (float)$value, 1, (float)$value);
				else
					$elements[] = new Slider("$i", $key, 1, 1000, 1, 1.0);
			}elseif(is_bool($value)) {
				$elements[] = new Toggle("$i", $key, $value);
			}elseif(is_string($value)) {
				$elements[] = new Input("$i", $key, "", $value);
			}
			$this->keys[] = $key;
			$i++;
		}
		$this->keys[] = "teleport";
		$elements[] = new Toggle("$i", $plugin->getLanguage()->get("generate.formteleport"), true);

		parent::__construct(
			TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("generate.form")]),
			$elements,
			function(Player $player, CustomFormResponse $response) use ($plugin) : void {
				$copy = [];
				foreach($response->getAll() as $key => $value) {
					if(isset($this->keys[((int)$key)-2]))
						$copy[$this->keys[((int)$key)-2]] = $value;
					else
						$copy[] = $value;
				}
				$data = $copy;

				$world = array_shift($data);
				if($player->getServer()->getWorldManager()->isWorldLoaded($world)) {
					$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("generate.exists", [$world]));
					return;
				}
				$teleport = array_pop($data);

				$blockIds = array_slice($data, -5, 5, true);
				$blockIds = array_map(function($val) {
					if(strpos($val, ':') !== false) {
						$peices = explode(':', $val);
						if(defined(BlockLegacyIds::class."::".strtoupper(str_replace(' ', '_', $peices[0]))))
							return constant(BlockLegacyIds::class."::".strtoupper(str_replace(' ', '_', $val))).':'.($peices[1] ?? 0);
						return $val;
					}elseif(is_numeric($val))
						return $val.':0';
					elseif(defined(BlockLegacyIds::class."::".strtoupper(str_replace(' ', '_', $val))))
						return constant(BlockLegacyIds::class."::".strtoupper(str_replace(' ', '_', $val))).':0';
					return $val;
				}, $blockIds);
				foreach($blockIds as $key => $val)
					$data[$key] = $val;

				if($plugin->generateLevel($world, array_shift($data), $data)) {
					if($teleport)
						$plugin->teleportPlayerToPlot($player, new Plot($world, 0, 0));
					$player->sendMessage($plugin->getLanguage()->translateString("generate.success", [$world]));
				}else{
					$player->sendMessage(TextFormat::RED . $plugin->getLanguage()->translateString("generate.error"));
				}
			}
		);
	}
}