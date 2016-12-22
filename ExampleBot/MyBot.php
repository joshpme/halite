<?php
require("halite/loader.php");

$map = new Halite\Map();

$map->init();

// You have 15 seconds to make up your mind what to do, use it wisely

$map->ready("MyBot");

while (true)
{
     
    // sort by highest production blocks
    usort($map->borders[0], "byProd");

    // Check and perform single step take overs 
    singleStepTakeover();
    
    // Check and perform two step take overs
    twoStepTakeover();
    
    $map->update();
}


function singleStepTakeover()
{
    global $map;
    
    foreach ($map->borders[0] as $border)
    {
        // Find my adjacnet blocks
        $adjacents = $border->adjacent($map->me);
        
        // Find if enough strength to take over
        $str = 0;
        $req = $border->str;
        foreach ($adjacents as $block)
            if ($block->move == 0)
            {
                $str += $block->str;
                if ($str > $req)
                        break;
            }
        // Take over block
        if ($str > $border->str)
        {
            $str = 0;
            $border->reserved = true;
            foreach ($adjacents as $block)
                if ($block->move == 0)
                {
                    debug($block . " directly taking over " . $border);
                    $block->moveTo($border);
                    
                    // if you have enough strength, dont reserve any more blocks
                    $str += $block->str;
                    if ($str > $req)
                        break;
                }
        }
    }
}

function twoStepTakeover()
{
    global $map;
    
    foreach ($map->borders[0] as $border)
    {
        if (!$border->reserved)
        {
            // Find my adjacnet blocks
            $adjacents = $border->adjacent($map->me);

            // Find available strength
            $req = $border->str;
            $str = 0;
            $checked = [];
            foreach ($adjacents as $block)
                if ($block->move == 0 && !$block->stuck)
                {
                    $str += $block->str + $block->prod;
                    foreach ($block->adjacent($map->me) as $helper)
                    {
                        if (!in_array($helper,$checked) && $helper->move == 0)
                        {
                            $checked[] = $helper;
                            $str += $helper->str;
                            if ($str > $req)
                                break;
                        }
                    }
                }
                
                
            // If strong enough, do perform
            if ($str > $req)
            {
                $border->reserved = true;
                $str = 0;
                $checked = [];
                foreach ($adjacents as $block)
                    if ($block->move == 0 && !$block->stuck)
                    {
                        $block->stuck = true;
                        $str += $block->str + $block->prod;
                        debug($block . " requesting assistance to take over " . $border);
                        foreach ($block->adjacent($map->me) as $helper)
                        {
                            if (!in_array($helper,$checked) && $helper->move == 0)
                            {
                                $checked[] = $helper;
                                $helper->moveTo($block);
                                $str += $helper->str;
                                if ($str > $req)
                                    break;
                            }
                        }
                    }
            }
        }
    }
}