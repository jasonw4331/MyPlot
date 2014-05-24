<?php
namespace MyPlot;

use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\command\PluginCommand;

class MyPlot_Commands extends PluginCommand{
    public function __construct(){
        parent::__construct(
            'plot',
            'MyPlot commands',
            '/plot [subcommand]',
            ['myplot']
        );
        $this->setPermission('myplot.command');
        $this->server = Server::getInstance();
    }

    public function execute(CommandSender $sender, $alias, array $args){
        console($sender->getName());
        if(!isset($args[0]))
            return false;
        $username = $sender->getName();

        switch(strtolower($args[0])){
            case 'newworld':
                if(count($args) !== 1)
                    return false;

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
                    if(!isset(MyPlot::$levelData['plotworld'.$i]))
                        break;
                }
                $levelName = 'plotworld'.$i;

                $this->server->generateLevel($levelName, NULL, "MyPlot\\MyPlot_Generator", $settings);

                $dir = MyPlot::$folder.'worlds/'.$levelName.'/';
                if(!is_dir($dir))
                    mkdir($dir);

                $levelData = array(20,7,64,[2,0],[3,0],[5,0],[44,0],[7,0]);
                file_put_contents($dir.'plots.data', json_encode($levelData));

                MyPlot::$levelData[$levelName] = $levelData;

                $this->server->loadLevel($levelName);
                $player = $this->server->getPlayer($username);
                $spawn = $this->server->getLevel($levelName)->getSpawn();
                $spawn->y += 2;
                $player->teleport($spawn);

                $msg = 'You successfully made a new plot world: '.$levelName;
                break;

            case 'claim':
                if(count($args) !== 1)
                    return false;

                $position = $this->server->getPlayer($username)->getPosition();
                $plot = MyPlot::getPlotByPos($position);

                if($plot === false){
                    $msg = 'You need to stand in a plot';
                    break;
                }

                if($plot->owner !== false){
                    $msg = 'This plot is already claimed by someone';
                    break;
                }

                $data = MyPlot::getPlayerData($username);
                $maxPlots = MyPlot::$config->get('MaxPlotsPerPlayer');
                if($data[0] >= $maxPlots and $this->server->isOp($username) === false){
                    $msg = "You can't own more than ".$maxPlots.' plots.';
                    break;
                }
                ++$data[0];
                $data[1][] = array($plot->id, $plot->levelName);
                MyPlot::savePlayerData($username, $data);

                $plot->owner = $username;
                $plot->save();
                $msg = 'You are now the owner of this plot with id: '.$plot->id[0].';'.$plot->id[1];
                break;

            case 'auto':
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
                    $msg = 'No free plot found';
                    break;
                }

                $plot = new MyPlot_Plot(array($plot[0], $plot[1]), $plot[2]);

                if($plot === false){
                    $msg = 'Error: could not load the plot data';
                    break;
                }

                $data = MyPlot::getPlayerData($username);
                $maxPlots = MyPlot::$config->get('MaxPlotsPerPlayer');
                if($data[0] >= $maxPlots and $this->server->isOp($username) === false){
                    $msg = "You can't own more than ".$maxPlots.' plots.';
                    break;
                }
                ++$data[0];
                $data[1][] = array($plot->id, $plot->levelName);
                MyPlot::savePlayerData($username, $data);

                $plot->owner = $username;
                $plot->save();
                $plot->teleport($username);
                $msg = 'You are now the owner of this plot with id: '.$plot->id[0].';'.$plot->id[1].' in level: '.$plot->levelName;
                break;

            case 'clear':
                $position = $this->server->getPlayer($username)->getPosition();
                $plot = MyPlot::getPlotByPos($position);

                if($plot === false){
                    $msg = 'You need to stand in a plot';
                    break;
                }

                if($plot->owner !== $username){
                    $msg = 'You are not an owner of this plot';
                    break;
                }

                $plot->clear();
                $msg = 'You have successfully cleared this plot';
                break;

            case 'delete':
                $position = $this->server->getPlayer($username)->getPosition();
                $plot = MyPlot::getPlotByPos($position);

                if($plot === false){
                    $msg = 'You need to stand in a plot.';
                    break;
                }

                if($plot->owner !== $username){
                    $msg = 'You are not an owner of this plot.';
                    break;
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
                $msg = 'You have successfully deleted this plot.';
                break;

            case 'addhelper':
                if(count($args) !== 2)
                    return false;

                $position = $this->server->getPlayer($username)->getPosition();
                $plot = MyPlot::getPlotByPos($position);

                if($plot === false){
                    $msg = 'You need to stand in a plot.';
                    break;
                }

                if($plot->owner !== $username){
                    $msg = 'You are not an owner of this plot.';
                    break;
                }

                if(!$plot->addHelper($args[1])){
                    $msg = $args[1].' is already a helper of this plot.';
                    break;
                }
                $plot->save();
                $msg = $args[1].' is added as helper of this plot.';
                break;

            case 'removehelper':
                if(count($args) !== 2)
                    return false;

                $position = $this->server->getPlayer($username)->getPosition();
                $plot = MyPlot::getPlotByPos($position);

                if($plot === false){
                    $msg = 'You need to stand in a plot.';
                    break;
                }

                if($plot->owner !== $username){
                    $msg = 'You are not an owner of this plot.';
                    break;
                }

                if(!$plot->removeHelper($args[1])){
                    $msg = $args[1].' is not a helper of this plot.';
                    break;
                }
                $plot->save();
                $msg = $args[1].' is removed as helper of this plot.';
                break;

            case 'info':
                $position = $this->server->getPlayer($username)->getPosition();
                $plot = MyPlot::getPlotByPos($position);

                if($plot === false){
                    $msg = 'You need to stand in a plot.';
                    break;
                }

                $msg = '==========[Plot info]=========='."\n";
                $msg .= 'ID: '.$plot->id[0].';'.$plot->id[1]."\n";
                if($plot->owner === false){
                    $msg .= 'This plot is not claimed yet.';
                    break;
                }
                $msg .= 'Owner: '.$plot->owner."\n";
                $msg .= 'Helpers: '.implode(', ', $plot->helpers)."\n";
                break;

            case 'comments':
                $position = $this->server->getPlayer($username)->getPosition();
                $plot = MyPlot::getPlotByPos($position);

                if($plot === false){
                    $msg = 'You need to stand in a plot.';
                    break;
                }

                if(empty($plot->comments)){
                    $msg = 'No comments found.';
                    break;
                }

                if(isset($args[1]) and is_numeric($args[1])){
                    $page = $args[1];
                    $totalPages = ceil(count($plot->comments)/10);
                    if($totalPages < $page){
                        $msg = "That page doesn't exit.";
                        break;
                    }
                    $msg = '==========[Page '.$page.'/'.ceil(count($plot->comments)/10).']=========='."\n";
                    foreach(array_splice($plot->comments, 10*$page, 10) as $comment){
                        $msg .= $comment[0].': '.$comment[1]."\n";
                    }
                }else{
                    $msg = '==========[Page 1/'.ceil(count($plot->comments)/10).']=========='."\n";
                    foreach(array_splice($plot->comments, 0, 10) as $comment){
                        $msg .= $comment[0].': '.$comment[1]."\n";
                    }
                }
                break;

            case 'comment':
                if(!isset($args[1])){
                    $msg = 'Usage: /plot comment <msg>';
                    break;
                }

                $position = $this->server->getPlayer($username)->getPosition();
                $plot = MyPlot::getPlotByPos($position);

                if($plot === false){
                    $msg = 'You need to stand in a plot.';
                    break;
                }

                array_shift($args);
                $comment = implode(' ', $args);

                array_unshift($plot->comments, array($username, $comment));
                $plot->save();
                $msg = 'Comment added.';
                break;

            case 'list':
                $data = MyPlot::getPlayerData($username);

                if(empty($data[1])){
                    $msg = 'You have no plots.';
                    break;
                }

                $msg = "==========[Your plots]==========\n";

                $amount = count($data[1]);
                for($i=0;$i<$amount;$i++){
                    $msg .= ($i+1).'. ID: '.$data[1][$i][0][0].';'.$data[1][$i][0][1].' Level: '.$data[1][$i][1]."\n";
                }
                break;

            case 'home':
                $data = MyPlot::getPlayerData($username);

                if(empty($data[1])){
                    $msg = 'You have no plots.';
                    break;
                }

                $amount = count($data[1]);
                if(isset($args[1]) and is_numeric($args[1])){
                    $plot = $args[1];
                    if($plot < 1 or $plot > $amount){
                        $msg = "That plot doesn't exist";
                        break;
                    }
                }else{
                    $plot = 1;
                }

                try{
                    $plot = new MyPlot_Plot($data[1][$plot-1][0], $data[1][$plot-1][1]);
                }catch(\Exception $e){
                    $msg = $e->getMessage();
                    break;
                }
                $plot->teleport($username);

                $msg = 'Teleported to your plot with ID: '.$plot->id[0].';'.$plot->id[1];

                break;

            default:
                $msg =  "====================[MyPlot commands]====================\n";
                $msg .= "/plot claim - Claim the plot you are standing in\n";
                $msg .= "/plot info - Gives information about a plot\n";
                $msg .= "/plot comments - Show all the comments of a plot\n";
                $msg .= "/plot comment <msg> - Add the command msg to a plot\n";
                $msg .= "/plot remove - Remove a plot\n";
                $msg .= "/plot clear - Clear a plot\n";
                $msg .= "/plot addhelper <player> - Add a player as helper to a plot\n";
                $msg .= "/plot removehelper <player> - Remove a player as helper from a plot\n";
        }
        $sender->sendMessage($msg);
        return true;
    }
}