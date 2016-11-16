<?php
# Build.php by @robske110 (modified)
$server = proc_open(PHP_BINARY.' src/pocketmine/PocketMine.php --no-wizard --disable-readline', [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
], $pipes);
fwrite($pipes[0], "makeplugin MyPlot\nstop\n\n");
while(!feof($pipes[1])){
    echo fgets($pipes[1]);
}
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);
echo "\n\nReturn value: ".proc_close($server)."\n";
if(count(glob('plugins/DevTools/MyPlot*.phar')) === 0){
    echo "Failed to create MyPlot.phar!\n";
    exit(1);
}else{
    $fn = glob('plugins/DevTools/MyPlot*');
    rename($fn[0], 'plugins/DevTools/MyPlot.phar');
    echo "MyPlot.phar created!\n";
    exit(0);
}
