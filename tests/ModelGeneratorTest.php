<?php

/**
 * @package ModelGenerator
 * @author Alejandro González thytanium@gmail.com
 * @license MIT
 * @link http://www.github.com/thytanium/model-generator
 */
class ModelGeneratorTest extends TestCase
{
    /**
     * Test that main class can be instantiated
     */
    public function testCanBeInstantiated()
    {
        $this->assertTrue(!is_null($this->generator));
    }
}