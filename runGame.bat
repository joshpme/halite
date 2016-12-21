@echo off
set seed=%1
set size=%2
SET XDEBUG_CONFIG=idekey=phpstorm-xdebug
.\halite.exe -n 1 -q -d %2 -s %seed% "php ExampleBot/MyBot.php"
