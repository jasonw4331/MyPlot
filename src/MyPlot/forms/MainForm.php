<?php
declare(strict_types=1);

namespace MyPlot\forms;

use cosmicpe\form\entries\simple\Button;
use cosmicpe\form\PaginatedForm;
use MyPlot\Commands;
use MyPlot\InternalAPI;
use MyPlot\MyPlot;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

class MainForm extends PaginatedForm{

	private const ENTRIES_PER_PAGE = 10;
	/**
	 * @var string[]
	 */
	private array $formNames;

	public function __construct(int $current_page, private Player $player, private MyPlot $owningPlugin, private InternalAPI $internalAPI){
		$owningPlugin = MyPlot::getInstance();
		/** @var Commands $mainCommand */
		$mainCommand = $owningPlugin->getCommand($owningPlugin->getLanguage()->get("command.name"));
		foreach($mainCommand->getCommands() as $subCommand){
			if($subCommand->getFormClass() !== null and $subCommand->canUse($player)){
				$name = (new \ReflectionClass($subCommand))->getShortName();
				$name = preg_replace('/([a-z])([A-Z])/s', '$1 $2', $name);
				$length = strlen($name) - strlen("SubCommand"); // TODO: validate
				$name = substr($name, 0, $length);
				$this->formNames[TextFormat::DARK_RED . ucfirst($name)] = $subCommand->getFormClass();
			}
		}
		parent::__construct(
			TextFormat::BLACK . MyPlot::getInstance()->getLanguage()->translateString("form.header", ["Main"]),
			'',
			$current_page
		);
	}

	protected function getPreviousButton() : Button{
		return new Button("<- Go back");
	}

	protected function getNextButton() : Button{
		return new Button("Next Page ->");
	}

	protected function getPages() : int{
		// Returns the maximum number of pages.
		// This will alter the visibility of previous and next buttons.
		// For example:
		//   * If we are on page 7 of 7, the "next" button wont be visible
		//   * If we are on page 6 of 7, the "next" and "previous" button WILL be visible
		//   * If we are on page 1 of 7, the "previous" button won't be visible
		return (int) ceil(count($this->formNames) / self::ENTRIES_PER_PAGE);
	}

	protected function populatePage() : void{
		// populate this page with buttons
		/**
		 * @var string     $formName
		 * @var MyPlotForm $formClass
		 */
		foreach($this->formNames as $formName => $formClass){
			$this->addButton(
				new Button($formName), // TODO: icons
				\Closure::fromCallable(function() use ($formClass){
					Await::f2c(
						function() use ($formClass){
							$plot = yield from $this->internalAPI->generatePlotByPosition($this->player->getPosition());
							$this->player->sendForm(new $formClass($this->owningPlugin, $this->player, $plot));
						}
					);
				})
			);
		}
	}

	protected function sendPreviousPage(Player $player) : void{
		$player->sendForm(new self($this->current_page - 1, $this->player, $this->owningPlugin, $this->internalAPI));
	}

	protected function sendNextPage(Player $player) : void{
		$player->sendForm(new self($this->current_page + 1, $this->player, $this->owningPlugin, $this->internalAPI));
	}
}