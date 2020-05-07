<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use pocketmine\form\FormValidationException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class NameForm extends ComplexMyPlotForm {
	public function __construct(Player $player, bool $redo = false) {
		parent::__construct(null);
		$plugin = MyPlot::getInstance();
		$this->setTitle($plugin->getLanguage()->translateString("form.header", [TextFormat::DARK_BLUE."Rename Form"]));

		if($redo)
			$this->addLabel(TextFormat::RED.$plugin->getLanguage()->get("form.redo"));

		if(isset($this->plot))
			$this->addInput("New Plot Title", $player->getDisplayName()."'s Plot", $this->plot->name);

		$this->setCallable(function(Player $player, ?string $data) use ($plugin) {
			if(is_null($data)) {
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name"), true);
				return;
			}
			$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name")." ".$plugin->getLanguage()->get("name.name")." \"$data\"", true);
		});
	}

	public function processData(&$data) : void {
		if(is_null($data))
			return;
		elseif(is_array($data) and !empty($data[0]))
			$data = $data[0];
		else
			throw new FormValidationException("Unexpected form data returned");
	}
}