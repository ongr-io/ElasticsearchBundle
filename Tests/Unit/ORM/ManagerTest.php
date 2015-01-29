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

use ONGR\ElasticsearchBundle\Mapping\ClassMetadataCollection;
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
        $manager = new Manager(null, $this->getClassMetadataCollectionMock());
        $this->assertNull($manager->getDocumentMapping('test'));
    }

    /**
     * Check if multiple repositories are created.
     */
    public function testGetRepositories()
    {
        $classMetadataMock = $this
            ->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadataMock
            ->expects($this->any())
            ->method('getType');

        $manager = new Manager(
            null,
            $this->getClassMetadataCollectionMock(
                [
                    'rep1' => $classMetadataMock,
                    'rep2' => clone $classMetadataMock,
                ]
            )
        );
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
     * @expectedExceptionMessage Undefined repository `rep1`, valid repositories are: `rep2`, `rep3`.
     */
    public function testGetRepositoriesException()
    {
        $manager = new Manager(
            null,
            $this->getClassMetadataCollectionMock(['rep2' => '', 'rep3' => ''])
        );
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
     * @expectedExceptionMessage Undefined repository `rep1`, valid repositories are: `rep2`, `rep3`.
     */
    public function testGetRepositoriesExceptionSingle()
    {
        $manager = new Manager(
            null,
            $this->getClassMetadataCollectionMock(['rep2' => '', 'rep3' => ''])
        );
        $manager->getRepository('rep1');
    }

    /**
     * Returns class metadata collection mock.
     *
     * @param array $metadata
     * @param array $typeMap
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ClassMetadataCollection
     */
    private function getClassMetadataCollectionMock($metadata = [], $typeMap = [])
    {
        $mock = $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\ClassMetadataCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $mock
            ->expects($this->any())
            ->method('getMetadata')
            ->will($this->returnValue($metadata));

        $mock
            ->expects($this->any())
            ->method('getTypeMap')
            ->will($this->returnValue($typeMap));

        return $mock;
    }
}
