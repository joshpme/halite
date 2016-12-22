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
     * @var Map
     */
    private $map;

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
    public function isBorder() {
        $blocks = $this->adjacent();
        foreach ($blocks as $block) {
            if ($block->owner != $this->owner) {
                return true;
            }
        }
        return false;
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
    public function moveTo(Block $block) {
        $directions = $this->direction($block);
        $this->move = $directions[0];
    }

    /**
     * Array of blocks as a path from this block to param block
     * 
     * @param Block $block
     * @return Block[]
     */
    public function path(Block $block) {
        // TODO
    }

    /**
     * Closest border block with matching owner id
     * WARNING: Relatively slow operation
     * 
     * @param int $owner
     * @return Block
     */
    public function closest($owner) {
        // to do make faster by determining size
        if (count($this->map->byOwner[$owner]) == 0) {
            return false;
        } elseif (true) {
            // FULL SCAN
            $distances = [];
            foreach ($this->map->borders[$owner] as $block) {
                $distances[$this->distance($block)] = $block;
            }
            ksort($distances);
            return reset($distances);
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

    /**
     * TODO: NOT FINISHED YET!!!!
     * Move towards a target block, using a path with the lowest cost
     *
     * @param Block $target
     */
    public function towards(Block $target) {
        $dx = $this->x - $target->x;
        $dy = $this->y - $target->y;

        // wrapping support
        if (abs($dx) > $this->map->width / 2) {
            $dx = $dx > 0 ? $dx - $this->map->width : $this->map->width + $dx;
        }
        if (abs($dy) > $this->map->height / 2) {
            $dy = $dy > 0 ? $dy - $this->map->height : $this->map->height + $dy;
        }

        $yl = $dy < 0 ? $this->y : $target->y;
        $yh = $dy < 0 ? $target->y : $this->y;

        $xl = $dx < 0 ? $this->x : $target->x;
        $xh = $dx < 0 ? $target->x : $this->x;

        echo "XL: " . $xl . "<br />";
        echo "XH: " . $xh . "<br />";
        if ($dx == 0) {
            $this->move = $dy > 0 ? UP : DOWN;
            return 0;
        }

        if ($dy == 0) {
            $this->move = $dx > 0 ? LEFT : RIGHT;
            return 0;
        }

        $xcost = [];
        $ycost = [];

        for ($y = $yl; $y <= $yh; $y++) {
            $ycost[$y] = 0;
            for ($x = $xl; $x <= $xh; $x++) {
                $block = $this->map->blocks[$x][$y];
                if ($block->owner != $this->owner) {
                    $ycost[$y] += $block->str;
                }
            }
        }


        echo "DX: " . $dx . "<br />";
        echo "DY: " . $dy . "<br />";
        for ($x = $xl; $x <= $xh; $x++) {
            $xcost[$x] = 0;
            for ($y = $yl; $y <= $yh; $y++) {
                $block = $this->map->blocks[$x][$y];
                if ($block->owner != $this->owner) {
                    $xcost[$x] += $block->str;
                }
            }
        }


        var_dump($xcost);
        var_dump($ycost);

        $targetx = array_search(min($xcost), $xcost);
        $targety = array_search(min($ycost), $ycost);

        $costy = min($ycost) / abs($dy);
        $costx = min($xcost) / abs($dx);

        if ($this->map->frame == 20) {


            echo "On frame " . $this->map->frame . "<br />";
            echo "I am block " . $this->x . "," . $this->y . "<br />";
//
        }

        //echo "Target X: " . $targetx . "<br />";
        //echo "Target Y: " . $targety . "<br />";

        if ($costy < $costx && $targety != $this->y || ($targetx == $this->x && $costy > $costx)) {
            if ($this->map->frame == 20) {
                echo "Max X: " . min($xcost) . "<br />";
                echo "Max Y: " . min($ycost) . "<br />";
                echo "Target row is Y Level " . $targety . "<br />";
            }
            //echo "Go to [" . $this->x . "][" . $targety . "]<br />";
            $this->move = $dy < 0 ? UP : DOWN;
        } else {
            if ($this->map->frame == 20) {
                echo "Max X: " . min($xcost) . "<br />";
                echo "Max Y: " . min($ycost) . "<br />";
                echo "Target row to X Level " . $targetx . "<br />";
                echo "Attempting to move " . $dx > 0 ? "LEFT" : "RIGHT";
            }
            //echo "Go to [" . $targetx . "][" . $this->y . "]<br />";
            $this->move = $dx < 0 ? LEFT : RIGHT;
        }
    }

}
