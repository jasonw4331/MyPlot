<?php
namespace MyPlot;

use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class MyPlot_Commands extends PluginCommand{
    private $server, $commands;

    public function __construct(MyPlot $plugin){
        parent::__construct('plot', $plugin);

        $this->setPermission('myplot.command');
        $this->setDescription('MyPlot commands');

        $this->server = Server::getInstance();
        $this->commands = array(
            'newworld',
            'claim',
            'auto',
            'clear',
            'delete',
            'addhelper',
            'removehelper',
            'info',
            'comments',
            'comment',
            'list',
            'home',
            'help'
        );
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . 'Please run this command in-game.');
            return true;
        }

        if(!isset($args[0])){
            $sender->sendMessage(TextFormat::YELLOW.'Usage: /plot <command>');
            $sender->sendMessage(TextFormat::YELLOW.wordwrap('The commands for MyPlot are: '.implode(', ', $this->commands), 60));
            return true;
        }

        $subCommand = strtolower($args[0]);
        if(!in_array($subCommand, $this->commands)){
            $sender->sendMessage(TextFormat::YELLOW."That command doesn't exist");
            $sender->sendMessage(TextFormat::YELLOW.wordwrap('The commands for MyPlot are: '.implode(', ', $this->commands), 60));
            return true;
        }

        $username = $sender->getName();

        array_shift($args);
        $this->{'command'.ucfirst($subCommand)}($username, $args, $sender);
        return true;
    }

    public function commandNewworld($username, $args, $sender){
        if($this->server->isOp($username) === false){
            $sender->sendMessage(TextFormat::RED."Only OP's can use this command!");
            return;
        }
        $settings = array(
            'PlotSize' => 20,
            'RoadWidth' => 7,
            'Height' => 64,
            'PlotFloorBlockId' => [2,0], // grass
            'PlotFillingBlockId' => [3,0], // dirt
            'RoadBlockId' => [5,0], // wooden planks
            'WallBlockId' => [44,0], // stone slab
            'BottomBlockId' => [7,0] // bedrock
        );

        $settings = array_merge($settings, MyPlot::$config->getAll());

        for($i=1;;$i++){
            if(!isset(MyPlot::$levelData['plotworld'.$i])){
                break;
            }
        }

        $levelName = 'plotworld'.$i;
        $this->server->generateLevel($levelName, NULL, "MyPlot\\MyPlot_Generator", $settings);

        $dir = MyPlot::$folder.'worlds/'.$levelName.'/';
        if(!is_dir($dir)){
            mkdir($dir);
        }

        $levelData = array(20,7,64,[2,0],[3,0],[5,0],[44,0],[7,0]);
        file_put_contents($dir.'plots.data', json_encode($levelData));

        MyPlot::$levelData[$levelName] = $levelData;

        $this->server->loadLevel($levelName);
        $player = $this->server->getPlayer($username);
        $spawn = $this->server->getLevel($levelName)->getSpawn();
        $spawn->y += 2;
        $player->teleport($spawn);

        $sender->sendMessage(TextFormat::GREEN.'You successfully made a new plot world: '.TextFormat::WHITE.$levelName);
    }

    public function commandClaim($username, $args, $sender){
        $position = $this->server->getPlayer($username)->getPosition();
        $plot = MyPlot::getPlotByPos($position);

        if($plot === false){
            $sender->sendMessage(TextFormat::RED.'You need to stand in a plot');
            return;
        }

        if($plot->owner !== false){
            $sender->sendMessage(TextFormat::RED.'This plot is already claimed by someone');
            return;
        }

        $data = MyPlot::getPlayerData($username);
        $maxPlots = MyPlot::$config->get('MaxPlotsPerPlayer');
        if($data[0] >= $maxPlots and $this->server->isOp($username) === false){
            $sender->sendMessage(TextFormat::RED."You can't own more than ".$maxPlots.' plots.');
            return;
        }

        ++$data[0];
        $data[1][] = array($plot->id, $plot->levelName);
        MyPlot::savePlayerData($username, $data);

        $plot->owner = $username;
        $plot->save();
        $sender->sendMessage(TextFormat::GREEN.'You are now the owner of this plot with id: '.TextFormat::WHITE.$plot->id[0].';'.$plot->id[1]);
    }

    public function commandAuto($username, $args, $sender){
        $worldsFolder = MyPlot::$folder.'worlds/';
        $plot = false;

        foreach(MyPlot::$levelData as $levelName => $levelData){
            $worldFolder = $worldsFolder.$levelName.'/';
            for($x=0;$x<16;$x++){
                for($z=0;$z<16;$z++){
                    if(!is_file($worldFolder.$x.'.'.$z.'.data')){
                        $plot = array($x, $z, $levelName);
                        break 3;
                    }
                }
            }
        }

        if($plot === false){
            $sender->sendMessage(TextFormat::RED.'No free plot found.');
            return;
        }

        $plot = new MyPlot_Plot(array($plot[0], $plot[1]), $plot[2]);

        if($plot === false){
            $sender->sendMessage(TextFormat::RED.'Could not load the plot data.');
            return;
        }

        $data = MyPlot::getPlayerData($username);
        $maxPlots = MyPlot::$config->get('MaxPlotsPerPlayer');
        if($data[0] >= $maxPlots and $this->server->isOp($username) === false){
            $sender->sendMessage(TextFormat::RED."You can't own more than ".TextFormat::WHITE.$maxPlots.' plots.');
            return;
        }

        ++$data[0];
        $data[1][] = array($plot->id, $plot->levelName);
        MyPlot::savePlayerData($username, $data);

        $plot->owner = $username;
        $plot->save();
        $plot->teleport($username);
        $sender->sendMessage(TextFormat::GREEN.'You are now the owner of this plot with id: '
                            .TextFormat::WHITE.$plot->id[0].';'.$plot->id[1].TextFormat::GREEN.' in level: '.TextFormat::WHITE.$plot->levelName);
    }

    public function commandClear($username, $args, $sender){
        $position = $this->server->getPlayer($username)->getPosition();
        $plot = MyPlot::getPlotByPos($position);

        if($plot === false){
            $sender->sendMessage(TextFormat::RED.'You need to stand in a plot');
            return;
        }

        if($plot->owner !== $username){
            $sender->sendMessage(TextFormat::RED.'You are not an owner of this plot.');
            return;
        }

        $plot->clear();
        $sender->sendMessage(TextFormat::GREEN.'You have successfully cleared this plot.');
    }

    public function commandDelete($username, $args, $sender){
        $position = $this->server->getPlayer($username)->getPosition();
        $plot = MyPlot::getPlotByPos($position);

        if($plot === false){
            $sender->sendMessage(TextFormat::RED.'You need to stand in a plot.');
            return;
        }

        if($plot->owner !== $username){
            $sender->sendMessage(TextFormat::RED.'You are not an owner of this plot.');
            return;
        }

        $data = MyPlot::getPlayerData($username);
        if(!empty($data[1])){
            $key = array_search(array($plot->id, $plot->levelName), $data[1]);
            unset($data[$key]);
            --$data[0];
            MyPlot::savePlayerData($username, $data);
        }

        $plot->clear();
        $plot->delete();
        $sender->sendMessage(TextFormat::GREEN.'You have successfully deleted this plot.');
    }

    public function commandAddhelper($username, $args, $sender){
        if(count($args) !== 1){
            $sender->sendMessage(TextFormat::YELLOW.'Usage: /plot addhelper <username>.');
            return;
        }

        $position = $this->server->getPlayer($username)->getPosition();
        $plot = MyPlot::getPlotByPos($position);

        if($plot === false){
            $sender->sendMessage(TextFormat::RED.'You need to stand in a plot.');
            return;
        }

        if($plot->owner !== $username){
            $sender->sendMessage(TextFormat::RED.'You are not an owner of this plot.');
            return;
        }

        if(!$plot->addHelper($args[0])){
            $sender->sendMessage(TextFormat::YELLOW.$args[1].' is already a helper of this plot.');
            return;
        }
        $plot->save();
        $sender->sendMessage(TextFormat::GREEN.$args[1].' is added as helper of this plot.');
    }

    public function commandRemovehelper($username, $args, $sender){
        if(count($args) !== 1){
            $sender->sendMessage(TextFormat::YELLOW.'Usage: /plot removehelper <username>.');
            return;
        }

        $position = $this->server->getPlayer($username)->getPosition();
        $plot = MyPlot::getPlotByPos($position);

        if($plot === false){
            $sender->sendMessage(TextFormat::RED.'You need to stand in a plot.');
            return;
        }

        if($plot->owner !== $username){
            $sender->sendMessage(TextFormat::RED.'You are not an owner of this plot.');
            return;
        }

        if(!$plot->removeHelper($args[0])){
            $sender->sendMessage(TextFormat::YELLOW.$args[1].' is not a helper of this plot.');
            return;
        }
        $plot->save();
        $sender->sendMessage(TextFormat::GREEN.$args[1].' is added as helper of this plot.');
    }

    public function commandInfo($username, $args, $sender){
        $position = $this->server->getPlayer($username)->getPosition();
        $plot = MyPlot::getPlotByPos($position);

        if($plot === false){
            $sender->sendMessage(TextFormat::RED.'You need to stand in a plot.');
            return;
        }

        $sender->sendMessage('==========[Plot info]==========');
        $sender->sendMessage('ID: '.$plot->id[0].';'.$plot->id[1]);
        if($plot->owner === false){
            $sender->sendMessage('This plot is not claimed yet.');
            return;
        }
        $sender->sendMessage('Owner: '.$plot->owner);
        $sender->sendMessage('Helpers: '.implode(', ', $plot->helpers));
    }

    public function commandComments($username, $args, $sender){
        $position = $this->server->getPlayer($username)->getPosition();
        $plot = MyPlot::getPlotByPos($position);

        if($plot === false){
            $sender->sendMessage(TextFormat::RED.'You need to stand in a plot.');
            return;
        }

        if(empty($plot->comments)){
            $sender->sendMessage('No comments found.');
            return;
        }

        $page = 0;

        if(isset($args[0]) and is_numeric($args[0])){
            $page = $args[0];
            $totalPages = ceil(count($plot->comments)/10);
            if($totalPages < $page){
                $sender->sendMessage(TextFormat::RED."That page doesn't exit.");
                return;
            }
        }
        $sender->sendMessage('==========[Page '.($page+1).'/'.ceil(count($plot->comments)/10).']==========');
        foreach(array_splice($plot->comments, 10*$page, 10) as $comment){
            $sender->sendMessage($comment[0].': '.$comment[1]);
        }
    }

    public function commandComment($username, $args, $sender){
        if(!isset($args[0])){
            $sender->sendMessage(TextFormat::YELLOW.'Usage: /plot comment <msg>');
            return;
        }

        $position = $this->server->getPlayer($username)->getPosition();
        $plot = MyPlot::getPlotByPos($position);

        if($plot === false){
            $sender->sendMessage(TextFormat::RED.'You need to stand in a plot.');
            return;
        }

        array_shift($args);
        $comment = implode(' ', $args);

        array_unshift($plot->comments, array($username, $comment));
        $plot->save();
        $sender->sendMessage(TextFormat::GREEN.'Comment added.');
    }

    public function commandList($username, $args, $sender){
        $data = MyPlot::getPlayerData($username);

        if(empty($data[1])){
            $sender->sendMessage(TextFormat::YELLOW.'You have no plots.');
            return;
        }

        $sender->sendMessage('==========[Your plots]==========');

        $amount = count($data[1]);
        for($i=0;$i<$amount;$i++){
            $sender->sendMessage(($i+1).'. ID: '.$data[1][$i][0][0].';'.$data[1][$i][0][1].' Level: '.$data[1][$i][1]);
        }
    }

    public function commandHome($username, $args, $sender){
        $data = MyPlot::getPlayerData($username);

        if(empty($data[1])){
            $sender->sendMessage(TextFormat::YELLOW.'You have no plots.');
            return;
        }

        $amount = count($data[1]);
        if(isset($args[0]) and is_numeric($args[0])){
            $plot = $args[0];
            if($plot < 1 or $plot > $amount){
                $sender->sendMessage(TextFormat::RED."That plot doesn't exist");
                return;
            }
        }else{
            $plot = 1;
        }

        try{
            $plot = new MyPlot_Plot($data[1][$plot-1][0], $data[1][$plot-1][1]);
        }catch(\Exception $e){
            $sender->sendMessage(TextFormat::RED.$e->getMessage());
            return;
        }
        $plot->teleport($username);

        $sender->sendMessage(TextFormat::GREEN.'Teleported to your plot with ID: '.TextFormat::WHITE.$plot->id[0].';'.$plot->id[1]);
    }

    public function commandHelp($username, $args, $sender){
        $sender->sendMessage('====================[MyPlot commands]====================');
        $sender->sendMessage('/plot claim - Claim the plot you are standing in');
        $sender->sendMessage('/plot info - Gives information about a plot');
        $sender->sendMessage('/plot comments - Show all the comments of a plot');
        $sender->sendMessage('/plot comment <msg> - Add the command msg to a plot');
        $sender->sendMessage('/plot remove - Remove a plot');
        $sender->sendMessage('/plot clear - Clear a plot');
        $sender->sendMessage('/plot addhelper <player> - Add a player as helper to a plot');
        $sender->sendMessage('/plot removehelper <player> - Remove a player as helper from a plot');
    }
}