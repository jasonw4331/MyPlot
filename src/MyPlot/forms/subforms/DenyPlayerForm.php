<?php
declare(strict_types=1);

namespace MyPlot\forms\subforms;

use cosmicpe\form\CustomForm;
use cosmicpe\form\entries\custom\DropdownEntry;
use MyPlot\forms\MyPlotForm;
use MyPlot\MyPlot;
use MyPlot\plot\BasePlot;
use MyPlot\plot\SinglePlot;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class DenyPlayerForm extends CustomForm implements MyPlotForm{
	/** @var string[] $players */
	private array $players = [];

	public function __construct(MyPlot $plugin, Player $player, ?BasePlot $plot){
		if(!$plot instanceof SinglePlot)
			throw new \InvalidArgumentException("Plot must be a SinglePlot");

		$players = [];
		if(!in_array("*", $plot->denied, true)){
			$players = ["*"];
			$this->players['*'] = ["*"];
		}
		foreach($plugin->getServer()->getOnlinePlayers() as $onlinePlayer){
			$players[] = $onlinePlayer->getDisplayName();
			$this->players[$onlinePlayer->getDisplayName()] = $onlinePlayer->getName();
		}
		parent::__construct(TextFormat::BLACK . $plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("denyplayer.form")]));
		$this->addEntry(
			new DropdownEntry(
				$plugin->getLanguage()->get("denyplayer.dropdown"),
				...array_map(
					function(string $text) : string{
						return TextFormat::DARK_BLUE . $text;
					},
					$players
				)
			),
			\Closure::fromCallable(fn(Player $player, DropdownEntry $entry) => $plugin->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name") . " " . $plugin->getLanguage()->get("denyplayer.name") . ' "' . $this->players[$entry->getValue()] . '"', true))
		);
	}
}