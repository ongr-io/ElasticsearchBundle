<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DSL\Suggester\Context;

use ONGR\ElasticsearchBundle\DSL\Suggester\Context\CategoryContext;
use ONGR\ElasticsearchBundle\DSL\Suggester\Context\GeoContext;
use ONGR\ElasticsearchBundle\Test\EncapsulationTestAwareTrait;

/**
 * Unit test for GeoContext.
 */
class GeoContextTest extends \PHPUnit_Framework_TestCase
{
    use EncapsulationTestAwareTrait;

    /**
     * Data provider for testToArray().
     *
     * @return array
     */
    public function getTestToArrayData()
    {
        return [
            // Case #1.
            [
                ['value' => 'testValue'],
                'testName',
                'testValue',
            ],
            // Case #2.
            [
                [
                    'value' => 'testValue',
                    'precision' => '5m',
                ],
                'testName',
                'testValue',
                '5m',
            ],
        ];
    }

    /**
     * Tests toArray method.
     *
     * @param array       $expected  Expected result.
     * @param string      $name      Name.
     * @param string      $value     Value.
     * @param string|null $precision Precision.
     *
     * @dataProvider getTestToArrayData
     */
    public function testToArray($expected, $name, $value, $precision = null)
    {
        $geoContext = new GeoContext($name, $value);
        $geoContext->setPrecision($precision);

        $result = $geoContext->toArray();
        $this->assertEquals($expected, $result);
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        $this->setStub(new GeoContext('foo', 'bar'));

        return 'ONGR\ElasticsearchBundle\DSL\Suggester\Context\GeoContext';
    }

    /**
     * @return array
     */
    public function getFieldsData()
    {
        return [
            ['precision'],
            ['name'],
            ['value'],
        ];
    }
}
