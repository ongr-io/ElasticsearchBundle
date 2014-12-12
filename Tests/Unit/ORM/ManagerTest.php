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
        $manager = new Manager(null, null, [], [], null);
        $this->assertNull($manager->getDocumentMapping('test'));
    }

    /**
     * Check if metadata collector returned is correct.
     */
    public function testGetMetadataCollector()
    {
        $metaDataCollector = new MetadataCollector(['test'], null);
        $manager = new Manager(null, $metaDataCollector, [], [], null);

        $this->assertEquals($metaDataCollector, $manager->getMetadataCollector());
    }

    /**
     * Check if multiple repositories are created.
     */
    public function testGetRepositories()
    {
        $manager = new Manager(null, null, [], ['rep1' => ['type' => ''], 'rep2' => ['type' => '']], null);
        $types = [
            'rep1',
            'rep2',
        ];
        $repository = $manager->getRepository($types);

        $this->assertEquals(new Repository($manager, $types), $repository);
    }

    /**
     * Check if an exception is thrown when an undefined repository is specified.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Undefined repository rep1, valid repositories are: rep2, rep3.
     */
    public function testGetRepositoriesException()
    {
        $manager = new Manager(null, null, [], ['rep2' => '', 'rep3' => ''], null);
        $types = [
            'rep1',
            'rep4',
        ];
        $manager->getRepository($types);
    }

    /**
     * Check if an exception is thrown when an undefined repository is specified and only a single rep is specified.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Undefined repository rep1, valid repositories are: rep2, rep3.
     */
    public function testGetRepositoriesExceptionSingle()
    {
        $manager = new Manager(null, null, [], ['rep2' => '', 'rep3' => ''], null);
        $manager->getRepository('rep1');
    }
}
