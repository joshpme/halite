<?php

define('STILL', 0);
define('NORTH', 1);
define('EAST', 2);
define('SOUTH', 3);
define('WEST', 4);

const DIRECTIONS = array(0, 1, 2, 3, 4);
const CARDINALS = array(1, 2, 3, 4);
const INVERSE = array(0,3, 4, 1, 2);

$names = array(0=>"Still",1=>"North",2=>"East",3=>"South",4=>"West");
define('ATTACK', 0);
define('STOP_ATTACK', 1);

class Location
{
    public $x;
    public $y;

    public function __construct($x = 0, $y = 0)
    {
        $this->x = $x;
        $this->y = $y;
    }
	
	public function isSame(Location $location)
	{
		return ($location->x == $this->x && $location->y == $this->y);
	}
	
}

class Site
{
    public $owner;
    public $strength;
    public $production;

    public function __construct($owner = 0, $strength = 0, $production = 0)
    {
        $this->owner = $owner;
        $this->strength = $strength;
        $this->production = $production;
    }
}

class Move
{
    public $loc;
    public $direction;

    public function __construct(Location $loc, $direction = STILL)
    {
        $this->loc = $loc;
        $this->direction = $direction;
    }
}

class GameMap
{
    public $width;
    public $height;
    public $contents;

    public function __construct($width = 0, $height = 0, $numberOfPlayers = 0)
    {
        $this->width = $width;
        $this->height = $height;
        $this->contents = [];

        for ($y = 0; $y < $this->height; ++$y) {
            $row = [];
            for ($x = 0; $x < $this->width; ++$x) {
                $row[] = new Site(0, 0, 0);
            }
            $this->contents[] = $row;
        }
    }

    public function inBounds(Location $l)
    {
        return $l->x >= 0 && $l->x < $this->width && $l->y >= 0 && $l->y < $this->height;
    }

	public function getDirection(Location $l1, Location $l2)
    {
		
		if ($l1->x == $l2->x && $l1->y == $l2->y)
		{
			return 0;
		}
		
		$leftRight = $l2->x - $l1->x;
		
		
		if ($l1->x > $l2->x)
		{
			$wrapLeftRight = ($l2->x + $this->width) - $l1->x;
		}
		else
		{
			$wrapLeftRight = ($l2->x - $this->width) - $l1->x;
		}
		
		if (abs($wrapLeftRight) < abs($leftRight))
		{
			$leftRight = $wrapLeftRight;
		}
		
		$upDown = $l2->y - $l1->y;
		if ($l1->y > $l2->y)
		{
			$wrapUpDown = ($l2->y + $this->height) - $l1->y;
		}
		else
		{
			$wrapUpDown = ($l2->y - $this->height) - $l1->y;
		}
		
		
		if (abs($wrapUpDown) < abs($upDown))
		{
			$upDown = $wrapUpDown;
		}
		
		if (abs($upDown) > abs($leftRight))
		{
			if ($upDown > 0)
			{
				return 3;
			}
			else
			{
				return 1;
			}
		}
		else
		{
			if ($leftRight > 0)
			{
				return 2;
			}
			else
			{
				return 4;
			}
		}
	}
	
    public function getDistance(Location $l1, Location $l2)
    {
        $dx = abs($l1->x - $l2->x);
        $dy = abs($l1->y - $l2->y);
        if ($dx > $this->width / 2) {
            $dx = $this->width - $dx;
        }
        if ($dy > $this->height / 2) {
            $dy = $this->height - $dy;
        }
        return $dx + $dy;
    }

    public function getAngle(Location $l1, Location $l2)
    {
        $dx = $l2->x - $l1->x;
        $dy = $l2->y - $l1->y;

        if ($dx > $this->width - $dx) {
            $dx -= $this->width;
        } elseif (-$dx > $this->width + $dx) {
            $dx += $this->width;
        }

        if ($dy > $this->height - $dy) {
            $dy -= $this->height;
        } elseif (-$dy > $this->height + $dy) {
            $dy += $this->height;
        }
        return atan2($dy, $dx);
    }

    public function getLocation(Location $loc, $direction)
    {
        $l = clone $loc;
        if ($direction !== STILL) {
            if ($direction === NORTH) {
                if ($l->y === 0) {
                    $l->y = $this->height - 1;
                } else {
                    $l->y -= 1;
                }
            } elseif ($direction === EAST) {
                if ($l->x === $this->width - 1) {
                    $l->x = 0;
                } else {
                    $l->x += 1;
                }
            } elseif ($direction === SOUTH) {
                if ($l->y === $this->height - 1) {
                    $l->y = 0;
                } else {
                    $l->y += 1;
                }
            } elseif ($direction === WEST) {
                if ($l->x === 0) {
                    $l->x = $this->width - 1;
                } else {
                    $l->x -= 1;
                }
            }
        }
        return $l;
    }

    public function getSite(Location $l, $direction = STILL)
    {
        $l = $this->getLocation($l, $direction);
        return $this->contents[$l->y][$l->x];
    }
}
