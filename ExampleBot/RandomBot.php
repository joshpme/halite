<?php
namespace Halite;
require("halite/loader.php");

$map = new Map();

$map->init();

$map->ready("RandomBot");

while (true)
{
    foreach ($map->myBlocks() as $block)
    {
        $block->move(rand(0,4));
    }

    $map->update();
}
