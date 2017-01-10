<?php
require("halite/loader.php");

$map = new Halite\Map();

$map->init();




$map->ready("MyBot");

while (true)
{
	foreach ($map->myBlocks() as $block)
	{
		if ($block->move == 0 && !$block->reserved)
		{
			$target = $block->closestHighProd(5);
			$path = $block->path($target);
			$i = 0;
			
			// find in-between target
			for ($i = 0; $i < count($path['blocks']); $i++)
			{
				if (!$path['blocks'][$i]->isMine())
				{
					$target = $path['blocks'][$i];
					break;
				}
				if ($path['blocks'][$i]->move != 0)
				{
					$target = null;
					break;
				}
			}
			
			if (is_null($target))
			{
				continue;
			}
			
			// find available strength
			$str = 0;
			for ($t = ($i - 1); $t > 0; $t--)
			{
				$pBlock = $path['blocks'][$t];
				$distance = $pBlock->distance($target);
				$prod = ($i - $distance) * $pBlock->prod;
				$str += $pBlock->str + $prod;
			}
			
			if ($str > $target->str)
			{
				continue;
			}
			
			$str += $block->str;
			
			// if enough strength, move towards target
			$moved = false;
			if ($str > $target->str)
			{
				$target->reserved = true;
				for ($t = 0; $t < $i; $t++)
				{
					$path['blocks'][$t]->reserved = true;
				}
				$block->move($path['directions'][0]);
			}
		}
	}
	
	
    $map->update();
}

