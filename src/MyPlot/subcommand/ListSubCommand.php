<?php
declare(strict_types=1);

namespace MyPlot\subcommand;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use SOFe\AwaitGenerator\Await;

class ListSubCommand extends SubCommand{
	public function canUse(CommandSender $sender) : bool{
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.list");
	}

	/**
	 * @param Player   $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool{
		Await::f2c(
			function() use ($sender, $args) : \Generator{
				if(count($args) > 0 and $sender->hasPermission("myplot.admin.list"))
					$plots = yield from $this->internalAPI->generatePlotsOfPlayer($args[0], null);
				else
					$plots = yield from $this->internalAPI->generatePlotsOfPlayer($sender->getName(), null);

				foreach($plots as $plot){
					$name = $plot->name;
					$x = $plot->X;
					$z = $plot->Z;
					$sender->sendMessage(TF::YELLOW . $this->translateString("list.found", [$name, $x, $z]));
				}
			}
		);
		return true;
	}
}