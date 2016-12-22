<?php
namespace Halite;
require("halite/loader.php");

$map = new Map();

$map->init();

// You have 15 seconds to make up your mind what to do, use it wisely

$map->ready("MyBot");

while (true)
{
    // direct take over of adjacent unowned blocks
    foreach ($map->myBorders() as $block)
        foreach ($block->adjacent(-1) as $adjacent)
            if ($block->str > $adjacent->str)
                $block->moveTo($adjacent);

    $map->update();
}
