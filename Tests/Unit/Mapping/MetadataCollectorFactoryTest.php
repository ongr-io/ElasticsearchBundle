<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Mapping;

use ONGR\ElasticsearchBundle\Mapping\MetadataCollectorFactory;

class MetadataCollectorFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return MetadataCollectorFactory
     */
    protected function getFactory()
    {
        return new MetadataCollectorFactory(
            [],
            __DIR__ . DIRECTORY_SEPARATOR . '/../../app/cache/test/annotations'
        );
    }

    /**
     * Tests get method.
     */
    public function testGet()
    {
        $factory = $this->getFactory();
        $factory->setDebug(true);
        $collector = $factory->get();

        $this->assertInstanceOf('ONGR\ElasticsearchBundle\Mapping\MetadataCollector', $collector);
    }
}
