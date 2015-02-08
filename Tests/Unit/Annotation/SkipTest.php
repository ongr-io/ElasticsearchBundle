<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Annotation;

use ONGR\ElasticsearchBundle\Annotation\Skip;

/**
 * Tests for Skip annotation.
 */
class SkipTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Data provider for testing constructor.
     *
     * @return array
     */
    public function getTestConstructorData()
    {
        return [
            [['value' => 'foo'], ['foo']],
            [['value' => ['bar']], ['bar']],
            [['value' => new \stdClass()], [], true],
        ];
    }

    /**
     * Tests if constuctor does what expected.
     *
     * @param array $options
     * @param array $expected
     * @param bool  $exception
     *
     * @dataProvider getTestConstructorData
     */
    public function testConstructor($options, $expected, $exception = false)
    {
        if ($exception) {
            $this->setExpectedException(
                'InvalidArgumentException',
                'Annotation `ONGR\ElasticsearchBundle\Annotation\Skip` unexpected type given.'
                . ' Expected string or array, given `object`'
            );
        }

        $skip = new Skip($options);
        $this->assertEquals($expected, $skip->value);
    }
}
