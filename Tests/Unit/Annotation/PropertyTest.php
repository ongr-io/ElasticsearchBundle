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

use ONGR\ElasticsearchBundle\Annotation\Property;

class PropertyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests if values are filtered correctly.
     */
    public function testFilter()
    {
        $type = new Property();

        $type->name = 'id';
        $type->index = 'no_index';
        $type->type = 'string';

        $this->assertEquals(
            [
                'type' => 'string',
            ],
            $type->dump(),
            'Properties should be filtered'
        );
    }
}
