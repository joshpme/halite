<?php

namespace Halite;

error_reporting(E_ALL);

require("halite/loader.php");

$map = new Map(true);

$map->init("MyBot");

while (true) 
{
    // find my first block
    $block = $map->first($map->me);

    $target = $map->get(0,0);


    while ($block) 
    {
        $block->move = 0;
        // if strength is above 50

        if ($block->str > 100)
        {
            $block->towards($target);
            //$block->move = $block->direction($map->get(0,0))[0];
        }

        // get my next block	
        $block = $block->next();
    }

    // send moves and get next frame
    $map->update();
}

