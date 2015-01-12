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
        $type = new Property(['name' => 'id', 'index' => 'no_index', 'type' => 'string', 'analyzer' => null]);

        $this->assertEquals(
            [
                'index' => 'no_index',
                'type' => 'string',
            ],
            $type->filter()
        );
    }
}
