<?php
declare(strict_types=1);
namespace MyPlot\forms\subforms;


use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use MyPlot\forms\ComplexMyPlotForm;
use MyPlot\MyPlot;
use pocketmine\form\FormValidationException;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class WarpForm extends ComplexMyPlotForm {
	/** @var Player $player */
	private $player;

	public function __construct(Player $player) {
		$plugin = MyPlot::getInstance();
		parent::__construct(
			TextFormat::BLACK.$plugin->getLanguage()->translateString("form.header", [$plugin->getLanguage()->get("warp.form")]),
			[
				new Input(
					"0",
					$plugin->getLanguage()->get("warp.formxcoord"),
					"2"
				),
				new Input(
					"1",
					$plugin->getLanguage()->get("warp.formzcoord"),
					"-4"
				),
				new Input(
					"2",
					$plugin->getLanguage()->get("warp.formworld"),
					"world",
					$player->getLevelNonNull()->getFolderName()
				)
			],
			function(Player $player, CustomFormResponse $response) use ($plugin) : void {
				if(is_numeric($response->getString("0")) and is_numeric($response->getString("1")) and $plugin->isLevelLoaded($response->getString("2")))
					$data =[
						(int)$response->getString("0"),
						(int)$response->getString("1"),
						$response->getString("2") === '' ? $player->getLevelNonNull()->getFolderName() : $response->getString("2")
					];
				elseif($response->getString("0") === '' and $response->getString("1") === '') {
					$player->sendForm(new self($player));
					throw new FormValidationException("Invalid form data returned");
				}else
					throw new FormValidationException("Unexpected form data returned");

				$player->getServer()->dispatchCommand($player, $plugin->getLanguage()->get("command.name")." ".$plugin->getLanguage()->get("warp.name")." $data[0];$data[1] $data[2]", true);
			}
		);
	}
}