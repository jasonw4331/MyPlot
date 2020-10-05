<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class BuySubCommand extends SubCommand
{
	/**
	 * @param CommandSender $sender
	 *
	 * @return bool
	 */
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player) and $sender->hasPermission("myplot.command.buy");
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if($this->getPlugin()->getEconomyProvider() === null){
			$command = new ClaimSubCommand($this->getPlugin(), "claim");
			return $command->execute($sender, []);
		}
		$plot = $this->getPlugin()->getPlotByPosition($sender->asPosition());
		if($plot === null){
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if($plot->name === $sender->getName() and !$sender->hasPermission("myplot.admin.buy")){
			$sender->sendMessage(TextFormat::RED . $this->translateString("buy.noself"));
			return true;
		}
		if($plot->price <= 0){
			$sender->sendMessage(TextFormat::RED . $this->translateString("buy.notforsale"));
			return true;
		}
		if(strtolower($args[0] ?? "") !== $this->translateString("confirm")){
			$sender->sendMessage($this->translateString("buy.confirm", [TextFormat::GREEN . "{$plot->X};{$plot->Z}" . TextFormat::RESET, TextFormat::GREEN . $plot->price . TextFormat::RESET]));
			return true;
		}
		$oldOwner = $this->getPlugin()->getServer()->getPlayer($plot->owner);
		$clone = clone $plot;
		$this->getPlugin()->buyPlot($plot, $sender);
		$sender->sendMessage($this->translateString("buy.success", [TextFormat::GREEN . "{$plot->X};{$plot->Z}" . TextFormat::RESET, TextFormat::GREEN . $clone->price . TextFormat::RESET]));
		if($oldOwner instanceof Player)
			$oldOwner->sendMessage($this->translateString("buy.sold", [TextFormat::GREEN . $sender->getName() . TextFormat::RESET, TextFormat::GREEN . "{$plot->X};{$plot->Z}" . TextFormat::RESET, TextFormat::GREEN . $clone->price . TextFormat::RESET]));
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		// TODO: Implement getForm() method.
		return null;
	}
}