<?php
declare(strict_types=1);
namespace MyPlot\forms;

use MyPlot\MyPlot;
use MyPlot\subcommand\SubCommand;
use pocketmine\form\FormValidationException;
use pocketmine\Player;

class MainForm extends MyPlotForm {

	/** @var string[] $link */
	private $link = [];

	/**
	 * MainForm constructor.
	 *
	 * @param MyPlot $plugin
	 * @param Player $player
	 * @param SubCommand[] $subCommands
	 */
	public function __construct(MyPlot $plugin, Player $player, array $subCommands) {
		$this->setTitle($plugin->getLanguage()->get("form.title"));

		$i = 0;
		foreach($subCommands as $name => $command) {
			if(!$command->canUse($player))
				continue;
			$this->addButton($name);
			$i++;
			$this->link[$i] = $name;
		}

		parent::__construct($plugin, function(Player $player, $data) {
			var_dump($player->getName(), $data);
			// TODO: open subcommand forms
		});
	}

	public function processData(&$data) : void {
		if(is_int($data))
			$data = $this->link[$data];
		else
			throw new FormValidationException("Unexpected form data returned");
	}
}