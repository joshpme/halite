# Better PHP Halite.io module

Cleaner to use halite.io PHP library

Currently only written for windows

## Installation

1. Put halite.exe in root folder
2. Run whole directory under a web server

## Usage 

1. Write your code in halite/MyBot.php
2. Preview your bot at preview/index.html

## Submission

1. Zip contents of ExampleBot
2. MyBot.php needs to be at the base level
3. Ensure your bot is called MyBot.php
4. Upload to Halite.io

## API

The API has to main components, Map and Blocks. 

### Map

```PHP

/**
 * Width of map
 * @var int
 */
public $width;

/**
 * Height of map
 * @var int
 */
public $height;

/**
 * Array of all blocks on map
 * @var Block[][]
 */
public $blocks;

/**
 * References to all blocks by a specific owner
 * @var Block[]
 */
public $byOwner;

/**
 * Reference to all border blocks
 * @var Block[]
 */
public $borders;

/**
 * My ID
 * @var int
 */
public $me;

/**
 * Enemy IDs
 * @var int[]
 */
public $enemies;

/*
 * Current frame #
 * Only populate after you declare you are ready
 * 
 * @var int
 */
public $frame;


/**
 * Get all your own blocks as an array
 * @return Block[]
 */
public function myBlocks()

/**
 * Get all your own border blocks as an array
 * @return Block[]
 */
public function myBorders()

/**
 * Performs initial setup
 * Receives map from game
 * 
 * @param string $name
 */
public function init()

/**
 * Lets the Game know you are ready to start sending moves;
 * By sending your name
 * 
 * @param type $name
 */
public function ready($name)

/**
 * Add a timing event to the Log
 * This will automatically include the current frame and the time elapsed
 * 
 * @param type $message
 */
public function timing($message)
/**
 * Returns a block at and X, Y location
 *
 * @param int $x
 * @param int $y
 *
 * @return Block
 */
public function get($x, $y)

/**
 * Send moves and get next frame
 */
public function update()

/**
 * Find the first block by an owner
 * 
 * @param int $owner
 * @return Block
 */
public function first($owner)

/**
 * Get the center block of a bunch of blocks
 *
 * @param array $blocks
 * @return Block
 */
function center(array $blocks)
```

### Block 

```PHP
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
 * Get the next block by the same owner
 * 
 * @param int $owner
 * @return Block
 */
public function next()

/**
 * Send or Receive Current Move
 * @param type $move
 * @return type
 */
public function move($move = -1)

/**
 * Get the block in a direction
 * 
 * @param int $direction
 * @return Block
 */
public function get($direction)

/**
 * Is this block mine?
 * @return bool
 */
public function isMine()

/**
 * Is this block unclaimed?
 * @return bool
 */
public function isFree()

/**
 * Is the current block owned by an enemy?
 * @return bool
 */
public function isEnemy()

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
public function adjacent($owners = null)

/**
 * Determines if the block is a border block
 * These are cached in $map->borders[$owner] for faster access
 * 
 * @return bool
 */
public function isBorder()

/**
 * Shortest distance to block (including wrapping)
 * eg. [0,0] -> [1,1] is two as it would take two moves
 * 
 * @param Block $block
 * @return int
 */
public function distance(Block $block)
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
public function direction(Block $block)

/**
 * Set move towards the general direction of another block
 * @param Block $block
 */
public function moveTo(Block $block)

/**
 * Closest border block with matching owner id
 * WARNING: Relatively slow operation
 * 
 * @param int $owner
 * @return Block
 */
public function closest($owner)

```

## Preview

Visit http://localhost/halite/preview/ will present a preview window that runs that game via exec.
