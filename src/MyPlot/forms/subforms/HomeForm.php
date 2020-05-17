<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use MyPlot\forms\SimpleMyPlotForm;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\form\FormValidationException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class HomeForm extends SimpleMyPlotForm {
	/** @var Plot[] $plots */
	private $plots = [];
	public function __construct(Player $player) {
		parent::__construct(null);
		$plugin = MyPlot::getInstance();
		$this->setTitle(TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("home.form")]));

		$this->plots = $plugin->getPlotsOfPlayer($player->getName(), $player->getLevel()->getFolderName());
		$i = 1;
		foreach($this->plots as $plot) {
			$this->addButton(TextFormat::DARK_RED.$i++.") ".$plot->name." ".(string)$plot);
		}

		$this->setCallable(function(Player $player, ?int $data) use ($plugin) {
			if(is_null($data)) {
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name"), true);
				return;
			}
			// TODO: merge list and home into one form
			$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name")." ".$plugin->getLanguage()->get("home.name")." \"$data\"", true);
		});
	}

	public function processData(&$data) : void {
		if(is_null($data))
			return;
		elseif(is_int($data))
			$data += 1;
		else
			throw new FormValidationException("Unexpected form data returned");
	}
}