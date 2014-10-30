<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\ORM;

use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use ONGR\ElasticsearchBundle\ORM\Manager;
use ONGR\ElasticsearchBundle\ORM\Repository;

/**
 * Unit tests for Manager.
 */
class ManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Check if null is returned when bundle mapping is empty.
     */
    public function testGetDocumentMapping()
    {
        $manager = new Manager(null, null, [], []);
        $this->assertNull($manager->getDocumentMapping('test'));
    }

    /**
     * Check if metadata collector returned is correct.
     */
    public function testGetMetadataCollector()
    {
        $metaDataCollector = new MetadataCollector(['test'], null);
        $manager = new Manager(null, $metaDataCollector, [], []);

        $this->assertEquals($metaDataCollector, $manager->getMetadataCollector());
    }

    /**
     * Check if multiple repositories are created.
     */
    public function testGetRepositories()
    {
        $manager = new Manager(null, null, [], []);
        $types = ['type1', 'type2'];
        $repository = $manager->getRepository($types);

        $this->assertEquals(new Repository($manager, $types), $repository);
    }
}
