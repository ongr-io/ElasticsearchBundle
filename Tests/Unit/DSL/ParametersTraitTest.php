<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DSL;

use ONGR\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Test for ParametersTrait.
 */
class ParametersTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ParametersTrait
     */
    private $mock;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->mock = $this->getMockForTrait('ONGR\ElasticsearchBundle\DSL\ParametersTrait');
    }

    /**
     * Tests getParameter method.
     */
    public function testGetParameter()
    {
        $this->assertFalse($this->mock->getParameter('unavailable_parameter'));
        $this->mock->addParameter('available_parameter', 123);
        $this->assertEquals(123, $this->mock->getParameter('available_parameter'));
    }

    /**
     * Tests addParameter method.
     */
    public function testAddParameter()
    {
        $this->mock->addParameter('available_parameter', 123);
        $this->assertEquals(123, $this->mock->getParameter('available_parameter'));
        $this->mock->addParameter('available_parameter', 321);
        $this->assertEquals(123, $this->mock->getParameter('available_parameter'));
    }
}
