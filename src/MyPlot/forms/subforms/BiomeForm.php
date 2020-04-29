<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;


use MyPlot\forms\SimpleMyPlotForm;
use MyPlot\MyPlot;
use pocketmine\form\FormValidationException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class BiomeForm extends SimpleMyPlotForm {
	/** @var string[] */
	private $biomeNames = [];

	/**
	 * BiomeForm constructor.
	 *
	 * @param string[] $biomes
	 */
	public function __construct(array $biomes) {
		parent::__construct(null);
		$plugin = MyPlot::getInstance();
		$this->setTitle($plugin->getLanguage()->translateString("form.header", [TextFormat::DARK_BLUE."Biome Form"]));

		$this->biomeNames = $biomes;
		foreach($biomes as $biomeName) {
			$this->addButton($biomeName); // TODO: add images
		}

		$this->setCallable(function(Player $player, ?string $data) use ($plugin) {
			if(is_null($data)) {
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name"), true);
				return;
			}
			$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name")." ".$plugin->getLanguage()->get("biome.name")." \"$data\"", true);
		});
	}

	public function processData(&$data) : void {
		if(is_null($data))
			return;
		elseif(is_int($data))
			$data = $this->biomeNames[$data];
		else
			throw new FormValidationException("Unexpected form data returned");
	}
}