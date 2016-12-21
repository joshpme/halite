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

require("Block.php");
require("Map.php");
