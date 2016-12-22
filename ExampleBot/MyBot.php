<?php
require("halite/loader.php");

$map = new Halite\Map();

$map->init();

// You have 15 seconds to make up your mind what to do, use it wisely

$map->ready("MyBot");

while (true)
{
    $free = $map->borders[0];
    //debug("Total free " . count($free));
    // sort by highest production blocks
    usort($free, "byProd");

    // Check and perform single step take overs 
    singleStepTakeover($free);
    
    // Check and perform two step take overs
    twoStepTakeover($free);
    
    //debug("My total blocks " . count($map->myBlocks()));
    foreach ($map->myBlocks() as $block)
    {
        if (!$block->isBorder && $block->str > 50)
        {
            $blocks = $block->closest(0);
            if ($blocks !== false)
            {
                $block->moveTo($blocks);
            }
        }
    }
    
    
    $map->update();
}


function singleStepTakeover($blocks)
{
    global $map;
    
    foreach ($blocks as $border)
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
                    //debug($block . " directly taking over " . $border);
                    $block->moveTo($border);
                    
                    // if you have enough strength, dont reserve any more blocks
                    $str += $block->str;
                    if ($str > $req)
                        break;
                }
        }
    }
}

function twoStepTakeover($blocks)
{
    global $map;
    
    foreach ($blocks as $border)
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
            {
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
                if ($str > $req)
                    break;
            }   
                
            // If strong enough, do perform
            if ($str > $req)
            {
                $border->reserved = true;
                $str = 0;
                $checked = [];
                foreach ($adjacents as $block)
                {
                    if ($block->move == 0 && !$block->stuck)
                    {
                        $block->stuck = true;
                        $str += $block->str + $block->prod;
                        //debug($block . " requesting assistance to take over " . $border);
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
                    if ($str > $req)
                        break;
                }
            }
        }
    }
}