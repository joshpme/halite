<?php
namespace Halite;
require("halite/loader.php");

$map = new Map();

$map->init();

$map->ready("MyBot");

while (true)
{
    foreach ($map->myBlocks() as $block)
    {
		if ($block->str > 100)
		{
			$block->move(rand(0,4));
		}
    }

    $map->update();
}
