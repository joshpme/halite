<?php

namespace Halite;

class Block {

    /**
     * Blocks horizontal position
     * @var int
     */
    public $x;

    /**
     * Blocks vertical position
     * @var int
     */
    public $y;

    /**
     * Blocks strength
     * @var int
     */
    public $str;

    /**
     * Block production
     * @var int
     */
    public $prod;

    /**
     * Blocks owner
     * @var int
     */
    public $owner;

    /**
     * Your next move for this block
     * @var int
     */
    public $move;
    
    /**
     * Cached flag for if border
     * @var bool
     */
    public $isBorder;
       
    /**
     * @var Map
     */
    private $map;
    
    /**
     * You can tag a piece if you'd like to stop something else from taking it.
     * 
     * @var boolean
     */
    public $reserved;
    
    /**
     * You can flag a block to say you want it to stay till
     * 
     * @var boolean
     */
    public $stuck;

    /**
     * @param Map $map
     * @param int $x
     * @param int $y
     */
    public function __construct($map, $x, $y) {
        $this->map = $map;
        $this->x = $x;
        $this->y = $y;
        $this->move = STILL;
        $this->reserved = false;
        $this->stuck = false;
    }

    public function __toString() {
        return "[" . $this->x . "," . $this->y . "] [Owner: " . $this->owner . "] [Str: " . $this->str . "] [Prod: " . $this->prod . "] [Border:" . ($this->isBorder ? "Yes" : "No") . "] ";
    }
    
    /**
     * Get the next block by the same owner
     * 
     * @param int $owner
     * @return Block
     */
    public function next() {
        $key = array_search($this, $this->map->byOwner[$this->owner]);
        if (isset($this->map->byOwner[$this->owner][$key + 1])) {
            return $this->map->byOwner[$this->owner][$key + 1];
        } else {
            return false;
        }
    }

    /**
     * Send or Receive Current Move
     * @param type $move
     * @return type
     */
    public function move($move = -1) {
        if ($move != -1) {
            $this->move = $move;
        }

        return $this->move;
    }

    /**
     * Get the block in a direction
     * 
     * @param int $direction
     * @return Block
     */
    public function get($direction) {
        $x = $this->x;
        $y = $this->y;

        switch ($direction) {
            case UP:
                $y = ($y == 0) ? $this->map->height - 1 : $y - 1;
                break;
            case DOWN:
                $y = ($y == $this->map->height - 1) ? 0 : $y + 1;
                break;
            case LEFT:
                $x = ($x == 0) ? $this->map->width - 1 : $x - 1;
                break;
            case RIGHT:
                $x = ($x == $this->map->width - 1) ? 0 : $x + 1;
                break;
        }

        return $this->map->get($x, $y);
    }

    /**
     * Is this block mine?
     * @return bool
     */
    public function isMine() {
        return ($this->owner == $this->map->me);
    }

    /**
     * Is this block unclaimed?
     * @return bool
     */
    public function isFree() {
        return ($this->owner == 0);
    }

    /**
     * Is the current block owned by an enemy?
     * @return bool
     */
    public function isEnemy() {
        return !$this->isMine() && !$this->isFree();
    }

    /**
     * Return all adjacent blocks
     * 
     * Optional owner filter
     * 
     * -1 is anyone but yourself
     * 
     * @param array[] $owners or int owner
     * @return Block[]
     */
    public function adjacent($owners = null) {
        if (!is_array($owners)) {
            if ($owners == -1) {
                $owners = array_merge($this->map->enemies, [0]);
            } else {
                $owners = [$owners];
            }
        }

        $blocks = [];

        foreach (CARDINALS as $direction) {
            $block = $this->get($direction);
            if (is_null($owners) || in_array($block->owner, $owners)) {
                $blocks[] = $this->get($direction);
            }
        }

        return $blocks;
    }

    /**
     * Determines if the block is a border block
     * These are cached in $map->borders[$owner] for faster access
     * 
     * @return bool
     */
    public function isBorder() 
    {
        if ($this->isBorder === true)
        {
            return true;
        }
        
        $found = false;
        
        foreach (CARDINALS as $direction) {
            $neighbour = $this->get($direction);
            if ($this->owner != $neighbour->owner) {
                $this->isBorder = true;
                $neighbour->isBorder = true;
                $found = true;
            }
        }
        
        if (!$found)
        {
            $this->isBorder = false;
            return false;
        }
        
        return true;
    }

    /**
     * Shortest distance to block (including wrapping)
     * eg. [0,0] -> [1,1] is two as it would take two moves
     * 
     * @param Block $block
     * @return int
     */
    public function distance(Block $block) {
        $dx = abs($this->x - $block->x);
        $dy = abs($this->y - $block->y);
        if ($dx > $this->map->width / 2) {
            $dx = $this->map->width - $dx;
        }
        if ($dy > $this->map->height / 2) {
            $dy = $this->map->height - $dy;
        }
        return $dx + $dy;
    }

    /**
     * Shortest direction to a block (including wrapping)
     * Perfect diagonals get two values.
     * Same location gets still.
     * 
     * Array always returned
     * 
     * @param Block $block
     * @return int[]
     */
    public function direction(Block $block) {

        // If same location
        if ($this->x == $block->x && $this->y == $block->y) {
            return [STILL];
        }

        $dx = $this->x - $block->x;
        $dy = $this->y - $block->y;

        // Wrapping support
        if (abs($dx) > $this->map->width / 2) {
            $dx = $dx > 0 ? $dx - $this->map->width : $this->map->width + $dx;
        }
        if (abs($dy) > $this->map->height / 2) {
            $dy = $dy > 0 ? $dy - $this->map->height : $this->map->height + $dy;
        }

        $directions = [];

        if ($dy != 0 && abs($dx) == abs($dy)) {
            $directions[] = $dx > 0 ? LEFT : RIGHT;
            $directions[] = $dy > 0 ? UP : DOWN;
        } elseif (abs($dy) > abs($dx)) {
            $directions[] = $dy > 0 ? UP : DOWN;
        } else {
            $directions[] = $dx > 0 ? LEFT : RIGHT;
        }

        return $directions;
    }

    /**
     * Set move towards the general direction of another block
     * @param Block $block
     */
    public function moveTo($block) {
        
        // if many blocks
        // pick one with highest production
        if (is_array($block))
        {
            if (reset($block)->owner == 0)
            {
                usort($block, "byProd");
                $block = reset($block);
            }
            else
            {
                $block = reset($block);
            }
        }
        $directions = $this->direction($block);
        $this->move = $directions[0];
    }


    /**
     * Closest border blocks with matching owner id
     * WARNING: Relatively slow operation
     * 
     * @param int $owner
     * @return Block[]
     */
    public function closest($owner) {
        // to do make faster by determining size
        if (count($this->map->byOwner[$owner]) == 0) 
        {
            echo "No more blocks by this owner";
            exit();
            return false;
        } elseif (true) {
            // FULL SCAN
            $distances = array_fill(0, $this->map->height * $this->map->width, []);
            foreach ($this->map->borders[$owner] as $block) {
                $distances[$this->distance($block)][] = $block;
            }
            
            ksort($distances);
            
            foreach ($distances as $distance)
            {
                if (count($distance) > 0)
                {
                    return $distance;
                }
            }
        } else {
            // SEARCH OUTWARDS
            // Could be useful functionality for something else, but finding
            // something by an owner is faster by searching though all borders
            // start cardinals first
            $search = [$this->get(UP), $this->get(RIGHT), $this->get(DOWN), $this->get(LEFT)];

            // search diagonals
            $search[] = $this->get(UP)->get(LEFT);
            $search[] = $this->get(UP)->get(RIGHT);
            $search[] = $this->get(DOWN)->get(LEFT);
            $search[] = $this->get(DOWN)->get(RIGHT);

            $i = 0;

            // while there are still items queued for searching
            while ($i < count($search)) {
                // get block to check
                $block = $search[$i];

                // check block for owner
                if ($block->owner == $owner) {
                    return $block;
                } else {
                    // only add more blocks if size is less than full height
                    if ($i <= $this->map->width * $this->map->height) {

                        // add blocks expanding outwards
                        $directions = $this->direction($block);
                        $add = $block;
                        foreach ($directions as $direction) {
                            $add = $add->get($direction);
                        }
                        $search[] = $add;

                        // if diagonal, add both diagonals too
                        if (count($directions) == 2) {
                            $search[] = $block->get($directions[0]);
                            $search[] = $block->get($directions[1]);
                        }
                    }
                }
                $i++;
            }

            return false;
        }
    }
	
	public function cost()
	{
		if ($this->owner == $this->map->me)
		{
			return 0;
		}
		elseif ($this->owner == 0)
		{
			return $this->str;
		}
		else
		{
			return -1;
			// calculate enemy cost
		}
	}
	
	public function closestHighProd($maxDistance)
	{
		// You have 15 seconds to make up your mind what to do, use it wisely
		$targets = $this->map->byOwner[0];
		usort($targets,"byProd");

		if (count($targets) == 0)
		{
			return null;
		}
		$prod = $targets[0]->prod;
		$closestTarget = null;
		$closest = -1;
		foreach ($targets as $target)
		{
			if ($target->prod < $prod && $closest != -1)
			{
				break;
			}
			else
			{
				$distance = $this->distance($target);
				if ($closest == -1 || $distance < $closest && !$target->reserved && $distance < $maxDistance)
				{
					$closest = $distance;
					$closestTarget = $target;
				}
			}
		}
		
		return $closestTarget;
	}

    /**
     * TODO: NOT FINISHED YET!!!!
     * Move towards a target block, using a path with the lowest cost
     *
     * @param Block $target
     */
    public function path(Block $target) {
		//echo "Path " . $this->x . "," . $this->y . " to " . $target->x . "," . $target->y . "<br />";
		
        $dx = $this->x - $target->x;
        $dy = $this->y - $target->y;

        // wrapping support
        if (abs($dx) > $this->map->width / 2) {
            $dx = $dx > 0 ? $dx - $this->map->width : $this->map->width + $dx;
        }
        if (abs($dy) > $this->map->height / 2) {
            $dy = $dy > 0 ? $dy - $this->map->height : $this->map->height + $dy;
        }
		

		//echo "Difference X: " . $dx . " Y: " . $dy . "<br />";

		$towards = [];
		if ($dx != 0)
		{
			$towards[] = $dx > 0 ? LEFT : RIGHT;
		}
		if ($dy != 0)
		{
			$towards[] = $dy > 0 ? UP : DOWN;
		}
		
		if ($this->distance($target) == 0)
		{
			return array("directions"=>[0],"blocks"=>array(), "cost"=>0, "prod"=>0);
		}
		if ($this->distance($target) == 1)
		{
			return array("directions"=>[$towards[0]], "blocks"=>array($target), "cost" => $target->cost(), "prod"=>$target->prod);
		}
		else
		{
			$costs = [];
			$paths = [];
			$prods = [];
			foreach ($towards as $direction)
			{
				$path = $this->get($direction)->path($target);
				$costs[$direction] = $this->get($direction)->cost() + $path['cost'];
				$prods[$direction] = $path['prod'] + $this->get($direction)->prod;
				$paths[$direction] = $path;
			}
			
			
			$min = min($costs);
			$directions = array_keys($costs, $min);
			
			$highestProd = -1;
			$highestDirection = -1;
			
			foreach ($directions as $direction)
			{
				if ($highestProd == -1 || $prods[$direction] > $highestProd)
				{
					$highestDirection = $direction;
					$highestProd = $prods[$direction];
				}
			}

			$direction = $highestDirection;
			
			array_unshift($paths[$direction]['directions'],$direction);
			array_unshift($paths[$direction]['blocks'],$this->get($direction));
			return array(
				"directions"=>$paths[$direction]['directions'],
				"blocks"=>$paths[$direction]['blocks'], 
				"cost" => $min,
				"prod"=>$prods[$direction]
			);
			
		}
		
    }

}
