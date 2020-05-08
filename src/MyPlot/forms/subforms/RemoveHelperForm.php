<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use pocketmine\form\FormValidationException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class RemoveHelperForm extends ComplexMyPlotForm {
	public function __construct() {
		parent::__construct(null);
		$plugin = MyPlot::getInstance();
		$this->setTitle(TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", ["Remove Helper Form"]));
		$this->addDropdown(
			$plugin->getLanguage()->translateString("removehelper.dropdown", ["Helper Name"]),
			$this->plot ? array_map(function(string $text) {
				return TextFormat::DARK_BLUE.$text;
			}, $this->plot->helpers) : []
		);

		$this->setCallable(function(Player $player, ?string $data) use ($plugin) {
			if(is_null($data)) {
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name"), true);
				return;
			}
			$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name")." ".$plugin->getLanguage()->get("removehelper.name")." \"$data\"", true);
		});
	}

	public function processData(&$data) : void {
		if(is_null($data))
			return;
		elseif(is_array($data))
			$data = $this->plot->helpers[$data[0]];
		else
			throw new FormValidationException("Unexpected form data returned");
	}
}