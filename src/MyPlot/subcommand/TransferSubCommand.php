<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\MyPlot;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class TransferSubCommand extends SubCommand
{

	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
	public function canUse(CommandSender $sender) : bool {
		return $sender->hasPermission("myplot.admin.transfer");
	}

	/**
	 * @param CommandSender $sender
	 * @param array $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(!isset($args[1])) {
			return false;
		}
		$fromUser = $args[0];
		$toUser = $args[1];
		/** TODO: Check if $toUser can bypass */
		$plots = MyPlot::getInstance()->getPlotsOfPlayer($fromUser, "");
		if(empty($plots)) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("transfer.noplots", [$fromUser]));
			return true;
		}
		foreach($plots as $plot) {
			$plot->owner = $toUser;
			MyPlot::getInstance()->savePlot($plot);
		}
		$sender->sendMessage(TextFormat::WHITE . $this->translateString("transfer.success", [count($plots), $fromUser, $toUser]));
		return true;
	}

}