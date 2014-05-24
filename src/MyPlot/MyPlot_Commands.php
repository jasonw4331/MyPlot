<?php
namespace MyPlot;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class MyPlot_Commands extends Command{
    public function __construct(){
        parent::__construct("plot", "MyPlot Commands", "/plot [action]", ["myplot"]);
        $this->setPermission('myplot.command');
        $this->server = Server::getInstance();
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Please run this command in-game.");
        }else{
            if(count($args) == 0){
                $msg =  "====================[MyPlot commands]====================\n";
                $msg .= "/plot claim - Claim the plot you are standing in\n";
                $msg .= "/plot info - Gives information about a plot\n";
                $msg .= "/plot comments - Show all the comments of a plot\n";
                $msg .= "/plot comment <msg> - Add the command msg to a plot\n";
                $msg .= "/plot remove - Remove a plot\n";
                $msg .= "/plot clear - Clear a plot\n";
                $msg .= "/plot add <player> - Add a player as helper to a plot\n";
                $msg .= "/plot remove <player> - Remove a player as helper from a plot\n";
                $sender->sendMessage($msg);
            }
            switch(strtolower($args)){
                case "claim":
                    
                    break;
                case "info":
                    
                    break;
                case "comments":
                    
                    break;
                case "comment":
                    
                    break;
                case "remove":
                    
                    break;
                case "clear":
                    
                    break;
                case "add":
                    
                    break;
                case "remove":
                    
                    break;
            }
        }
    }
}