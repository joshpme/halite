<?php

const STILL = 0;
const UP = 1;
const RIGHT = 2;
const DOWN = 3;
const LEFT = 4;

const NAMES = array(
    STILL => "Still",
    UP => "Up",
    RIGHT => "Right",
    DOWN => "Down",
    LEFT => "Left"
);

const DIRECTIONS = array(STILL, UP, RIGHT, DOWN, LEFT);
const CARDINALS = array(UP, RIGHT, DOWN, LEFT);
const INVERSE = array(STILL, DOWN, LEFT, UP, RIGHT);

function debug($message,$frame = -1)
{
    global $map;
    if ($frame == -1 || $frame == $map->frame)
    {
        file_put_contents("debug.txt","[#" . $map->frame . "] " . $message . "\r\n", FILE_APPEND);
    }
}

function byProd($a, $b)
{
    if ($a->prod == $b->prod) {
        return 0;
    }
    return ($a->prod < $b->prod) ? -1 : 1;
}


require("Block.php");
require("Map.php");
