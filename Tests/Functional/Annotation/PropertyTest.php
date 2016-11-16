<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Annotation;

use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class PropertyTest extends AbstractElasticsearchTestCase
{
    /**
     * Test if field names are correctly generated from property names.
     */
    public function testIfNamesFormedCorrectly()
    {
        $mappings = $this->getManager()->getMetadataCollector()->getMapping('TestBundle:User');

        $expected = [
            'first_name' => [
                'type' => 'text',
            ],
            'last_name' => [
                'type' => 'text',
            ],
        ];

        $this->assertEquals($expected, $mappings['properties']);
    }
}
