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
    private $parametersTraitMock;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->parametersTraitMock = $this->getMockForTrait('ONGR\ElasticsearchBundle\DSL\ParametersTrait');
    }

    /**
     * Tests getParameter method.
     */
    public function testGetParameter()
    {
        $this->assertFalse($this->parametersTraitMock->getParameter('unavailable_parameter'));
        $this->parametersTraitMock->addParameter('available_parameter', 123);
        $this->assertEquals(123, $this->parametersTraitMock->getParameter('available_parameter'));
    }

    /**
     * Tests addParameter method.
     */
    public function testAddParameter()
    {
        $this->parametersTraitMock->addParameter('available_parameter', 123);
        $this->assertEquals(123, $this->parametersTraitMock->getParameter('available_parameter'));
        $this->parametersTraitMock->addParameter('available_parameter', 321);
        $this->assertEquals(123, $this->parametersTraitMock->getParameter('available_parameter'));
    }
}
