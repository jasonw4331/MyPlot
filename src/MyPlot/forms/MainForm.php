<?php
declare(strict_types=1);
namespace MyPlot\forms;

use MyPlot\MyPlot;
use MyPlot\subcommand\SubCommand;
use pocketmine\form\FormValidationException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class MainForm extends SimpleMyPlotForm {

	/** @var SubCommand[] $link */
	private $link = [];

	/**
	 * MainForm constructor.
	 *
	 * @param MyPlot $plugin
	 * @param Player $player
	 * @param SubCommand[] $subCommands
	 */
	public function __construct(MyPlot $plugin, Player $player, array $subCommands) {
		$this->setTitle($plugin->getLanguage()->translateString("form.header", [TextFormat::AQUA."MyPlot Forms List"]));

		foreach($subCommands as $name => $command) {
			if(!$command->canUse($player) and $command->getForm() !== null)
				continue;
			$this->addButton(TextFormat::YELLOW.strtoupper($name));
			$this->link[] = $name;
		}

		parent::__construct($plugin, function(Player $player, ?MyPlotForm $data) use($plugin) {
			if(is_null($data))
				return;
			$data->setPlot($plugin->getPlotByPosition($player));
			$player->sendForm($data);
		});
	}

	public function processData(&$data) : void {
		if(is_int($data))
			$data = $this->link[$data]->getForm();
		elseif(is_null($data))
			return;
		else
			throw new FormValidationException("Unexpected form data returned");
	}
}