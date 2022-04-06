<?php
declare(strict_types=1);

namespace MyPlot\subcommand;

use MyPlot\forms\subforms\ClaimForm;
use MyPlot\plot\BasePlot;
use MyPlot\plot\SinglePlot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;

class ClaimSubCommand extends SubCommand{
	public function canUse(CommandSender $sender) : bool{
		return $sender->hasPermission("myplot.command.claim") and $sender instanceof Player;
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
				$pos = $sender->getPosition();
				$x = $pos->x;
				$z = $pos->z;
				$levelName = $sender->getWorld()->getFolderName();
				$plot = $this->internalAPI->getPlotFast($x, $z, $this->internalAPI->getLevelSettings($levelName));

				$name = "";
				switch(count($args)){
					case 1:
						if(str_contains($args[0], ';')){
							$coords = explode(';', $args[0]);
							if(count($coords) !== 2 or !is_numeric($coords[0]) or !is_numeric($coords[1])){
								$sender->sendMessage(TextFormat::RED . 'Usage: ' . $this->translateString('claim.usage'));
								return;
							}
							$plot = new BasePlot($levelName, (int) $coords[0], (int) $coords[1]);
						}else{
							$name = $args[0];
						}
						break;
					case 2:
						if(!str_contains($args[0], ';')){
							$sender->sendMessage(TextFormat::RED . 'Usage: /plot claim <X;Z> <name: string>');
							return;
						}
						$coords = explode(';', $args[0]);
						if(count($coords) !== 2 or !is_numeric($coords[0]) or !is_numeric($coords[1])){
							$sender->sendMessage(TextFormat::RED . 'Usage: /plot claim <X;Z> <name: string>');
							return;
						}
						$plot = new BasePlot($levelName, (int) $coords[0], (int) $coords[1]);
						$name = $args[1];
				}
				if($plot === null){
					$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
					return;
				}
				$plot = yield from $this->internalAPI->generatePlot($plot);
				if(!$plot instanceof SinglePlot)
					$plot = SinglePlot::fromBase($plot);

				if($plot->owner !== ""){
					if($plot->owner === $sender->getName()){
						$sender->sendMessage(TextFormat::RED . $this->translateString("claim.yourplot"));
					}else{
						$sender->sendMessage(TextFormat::RED . $this->translateString("claim.alreadyclaimed", [$plot->owner]));
					}
					return;
				}
				$maxPlots = $this->plugin->getMaxPlotsOfPlayer($sender);
				if(count(yield from $this->internalAPI->generatePlotsOfPlayer($sender->getName(), null)) >= $maxPlots){
					$sender->sendMessage(TextFormat::RED . $this->translateString("claim.maxplots", [$maxPlots]));
					return;
				}
				$economy = $this->internalAPI->getEconomyProvider();
				if($economy !== null and !(yield from $economy->reduceMoney($sender, $plot->price, 'used plot claim command'))){
					$sender->sendMessage(TextFormat::RED . $this->translateString("claim.nomoney"));
					return;
				}
				if(yield from $this->internalAPI->generateClaimPlot($plot, $sender->getName(), $name)){
					$sender->sendMessage($this->translateString("claim.success"));
				}else{
					$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
				}
			}
		);
		return true;
	}

	public function getFormClass() : ?string{
		return ClaimForm::class;
	}
}