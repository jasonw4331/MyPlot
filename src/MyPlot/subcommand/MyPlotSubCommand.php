<?php
declare(strict_types=1);

namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use pocketmine\command\CommandSender;

interface MyPlotSubCommand{

	public function getUsage() : string;

	public function getName() : string;

	public function getDescription() : string;

	public function getAlias() : string;

	public function canUse(CommandSender $sender) : bool;

	public function execute(CommandSender $sender, array $args) : bool;

	/**
	 * @return MyPlotForm|null
	 */
	public function getFormClass() : ?string;
}