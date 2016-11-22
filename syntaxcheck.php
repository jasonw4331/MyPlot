<?php
$base = glob("plugins/MyPlot/src/*.php");
$providers = glob("plugins/MyPlot/src/provider/*.php");
$commands = glob("plugins/MyPlot/src/subcommand/*.php");
$tasks = glob("plugins/MyPlot/src/Tasks/*.php");
$files = array_merge($base, $providers, $commands, $tasks);
$output = [];
foreach($files as $file){
    exec("php -l $file", $output);
}
foreach($output as $line){
    echo($line);
}
