<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\Commands;
use MyPlot\forms\MyPlotForm;
use MyPlot\MyPlot;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;
use function array_shift;
use function count;
use function is_numeric;

class HelpSubCommand extends SubCommand
{
	/** @var Commands $cmds */
	private $cmds;

	/**
	 * HelpSubCommand constructor.
	 *
	 * @param MyPlot $plugin
	 * @param string $name
	 * @param Commands $cmds
	 */
	public function __construct(MyPlot $plugin, string $name, Commands $cmds) {
		parent::__construct($plugin, $name);
		$this->cmds = $cmds;
	}

	public function canUse(CommandSender $sender) : bool {
		return $sender->hasPermission("myplot.command.help");
	}

	/**
	 * @param CommandSender $sender
	 * @param string[] $args
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
        if(count($args) === 0) {
            $pageNumber = 1;
        }elseif(is_numeric($args[0])) {
            $pageNumber = (int) array_shift($args);
            if ($pageNumber <= 0) {
                $pageNumber = 1;
            }
        }else{
            return false;
        }

        $sender->sendMessage(MyPlot::PREFIX.C::GOLD."Hilfe zur Grundstück-Verwaltung ".C::DARK_GRAY."-".C::YELLOW." Seite ".$pageNumber);
        switch ($pageNumber){
            case 1:
                $sender->sendMessage(C::YELLOW."/plot claim ".C::GRAY."Grundstück unter dir beanspruchen");
                $sender->sendMessage(C::YELLOW."/plot auto ".C::GRAY."Automatisch ein freies Grundstück finden");
                $sender->sendMessage(C::YELLOW."/plot home <Spieler/Nummer> ".C::GRAY."Zu einem Spieler-Grundstück oder zu deinem Grundstück teleportieren");
                $sender->sendMessage(C::YELLOW."/plot trust <Spieler> ".C::GRAY."Spieler als Helfer hinzufügen");
                $sender->sendMessage(C::YELLOW."/plot remove <Spieler> ".C::GRAY."Spieler als Helfer entfernen");
                $sender->sendMessage(C::YELLOW."/plot deny <Spieler> ".C::GRAY."Spieler von deinem Grundstück sperren");
                $sender->sendMessage(C::YELLOW."/plot undeny <Spieler>".C::GRAY."Sperrungs eines Spielers aufheben");
                $sender->sendMessage(C::YELLOW."/plot info ".C::GRAY."Erhalte Informationen zu dem Grundstück auf dem du stehst");
                break;
            case 2:
                $sender->sendMessage(C::YELLOW."/plot pvp ".C::GRAY."Aktiviere Kämpfen auf deinem Grundstück");
                $sender->sendMessage(C::YELLOW."/plot homes ".C::GRAY."Eine Liste deiner Grundstücke");
                $sender->sendMessage(C::YELLOW."/plot middle ".C::GRAY."Teleportiert dich in die Mitte eines Grundstücks");
                $sender->sendMessage(C::YELLOW."/plot clear ".C::GRAY."Leere dein Grundstück");
                $sender->sendMessage(C::YELLOW."/plot reset ".C::GRAY."Setze dein Grundstück ganz zurück");
        }
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return null;
	}
}