<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;

use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use pocketmine\form\FormValidationException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class KickForm extends ComplexMyPlotForm {
	/** @var string[] $players */
	private $players = [];

	public function __construct() {
		parent::__construct(null);
		$plugin = MyPlot::getInstance();
		$this->setTitle($plugin->getLanguage()->translateString("form.header", [TextFormat::DARK_BLUE."Add Helper Form"]));
		$players = [];
		foreach($plugin->getServer()->getOnlinePlayers() as $player) {
			if(isset($this->plot) and !$plugin->getPlotByPosition($player)->isSame($this->plot))
				continue;
			$players[] = $player->getDisplayName();
			$this->players[] = $player->getName();
		}
		$this->addDropdown(
			$plugin->getLanguage()->translateString("kick.dropdown", [TextFormat::WHITE."Player Name"]),
			$players
		);

		$this->setCallable(function(Player $player, ?string $data) use ($plugin) {
			if(is_null($data)) {
				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name"), true);
				return;
			}
			$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name")." ".$plugin->getLanguage()->get("kick.name")." \"$data\"", true);
		});
	}

	public function processData(&$data) : void {
		if(is_null($data))
			return;
		elseif(is_array($data)) {
			$key = array_search($data[0], $this->players);
			if($key === false)
				throw new FormValidationException("Invalid form data returned");
			$data = $this->players[$key];
		}else
			throw new FormValidationException("Unexpected form data returned");
	}
}