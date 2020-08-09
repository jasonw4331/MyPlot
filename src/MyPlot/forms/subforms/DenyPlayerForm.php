<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\form\FormValidationException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class DenyPlayerForm extends ComplexMyPlotForm {
	/** @var string[] $players */
	private $players = [];

	public function __construct(Plot $plot) {
		parent::__construct(null);
		$plugin = MyPlot::getInstance();
		$this->setTitle(TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("denyplayer.form")]));
		$players = [];
		if(!in_array("*", $plot->denied)) {
			$players = ["*"];
			$this->players = ["*"];
		}
		foreach($plugin->getServer()->getOnlinePlayers() as $player) {
			$players[] = $player->getDisplayName();
			$this->players[] = $player->getName();
		}
		$this->addDropdown($plugin->getLanguage()->get("denyplayer.dropdown"),
			array_map(function(string $text) {
				return TextFormat::DARK_BLUE.$text;
			}, $players)
		);

		$this->setCallable(function(Player $player, ?string $data) use ($plugin) {
			if(is_null($data)) {
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name"), true);
				return;
			}
			$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name")." ".$plugin->getLanguage()->get("denyplayer.name")." \"$data\"", true);
		});
	}

	public function processData(&$data) : void {
		if(is_null($data))
			return;
		elseif(is_array($data))
			$data = $this->players[$data[0]];
		else
			throw new FormValidationException("Unexpected form data returned");
	}
}