<?php

namespace Halite;

require("loader.php");

use PHPUnit_Framework_TestCase;

class MapTest extends PHPUnit_Framework_TestCase
{
    public $test;
    public function setUp()
    {
        $this->test = new Map(true, 10);
        $this->test->init("TestBot");

        parent::setUp();
    }

    public function testDebugInit()
    {
        $this->assertGreaterThan(0,count($this->test->byOwner[$this->test->me]));

    }

    public function testBorders()
    {
        if (count($this->test->byOwner[0]) > 0)
        {
            $this->assertGreaterThan(0,count($this->test->borders[0]));
        }
    }
};