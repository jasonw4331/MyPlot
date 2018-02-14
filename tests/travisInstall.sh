#!/bin/bash
set -e
if [ "$TRAVIS" != "true" ]; then
    echo Please only run this script on Travis-CI
    exit 1
fi

PM_DL_PATH="${1:-"https://github.com/pmmp/PocketMine-MP/releases/download/api%2F3.0.0-ALPHA11/PocketMine-MP_1.7dev-677_07bf1c9e_API-3.0.0-ALPHA11.phar"}"

mkdir "$TRAVIS_BUILD_DIR"/../PocketMine && cd "$TRAVIS_BUILD_DIR"/../PocketMine
echo "Installing PocketMine in $PWD"
echo "Downloading PocketMine build from Poggit"
wget -O PocketMine-MP.phar "$PM_DL_PATH"
mkdir plugins && wget -O plugins/PluginChecker.phar https://poggit.pmmp.io/res/PluginChecker.phar

echo "Downloading Poggit build"
mkdir unstaged
wget -O - https://poggit.pmmp.io/res/travisPluginTest.php | php -- unstaged

echo "Installed allthethings. Execute https://poggit.pmmp.io/travisScript.sh in the script phase to execute test."
exit 0