<?php

namespace Halite;

require("halite/loader.php");

$map = new Map(true);


$map->init("RandomBot");
while (true)
{

    // find my first block
    $block = $map->first($map->me);

    do {
        $block->move = 0;
        // get my next block
    } while ($block = $block->next($map->me));

    // send moves and get next frame
    $map->update();
}