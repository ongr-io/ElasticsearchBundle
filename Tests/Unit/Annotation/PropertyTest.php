<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Unit\Annotation;

use Ongr\ElasticsearchBundle\Annotation\Property;

class PropertyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests if values are filtered correctly.
     */
    public function testFilter()
    {
        $type = new Property(
            [
                'object_name' => 'foo/bar',
                'type' => 'string',
            ]
        );

        $this->assertEquals('foo/bar', $type->objectName, 'Properties should be camelized');

        $type->name = 'id';
        $type->index = 'no_index';
        $type->analyzer = null;

        $this->assertEquals(
            [
                'index' => 'no_index',
                'type' => 'string',
            ],
            $type->dump(),
            'Properties should be filtered'
        );
    }
}
