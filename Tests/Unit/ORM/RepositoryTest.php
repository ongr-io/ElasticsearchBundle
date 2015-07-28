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

use ONGR\ElasticsearchBundle\Mapping\ClassMetadata;
use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchDSL\Search;

class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Data provider for testExecute().
     *
     * @return array
     */
    public function getExecuteData()
    {
        $out = [];

        $namespace = 'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document\\';
        // Case #0 Single type.
        $out[] = [
            ['AcmeTestBundle:Product'],
            ['product'],
            [
                'AcmeTestBundle:Product' => $this->getClassMetadata(
                    [
                        'type' => 'product',
                        'fields' => [],
                        'namespace' => $namespace . 'Product',
                    ]
                ),
            ],
        ];

        // Case #1 Multi types.
        $out[] = [
            [],
            [
                'product',
                'content',
            ],
            [
                'AcmeTestBundle:Product' => $this->getClassMetadata(
                    [
                        'type' => 'product',
                        'fields' => [],
                        'namespace' => $namespace . 'Product',
                    ]
                ),
                'AcmeTestBundle:Content' => $this->getClassMetadata(
                    [
                        'type' => 'content',
                        'fields' => [],
                        'namespace' => $namespace . 'Content',
                    ]
                ),
            ],
        ];

        return $out;
    }

    /**
     * Test for getTypes().
     *
     * @param array           $types
     * @param array           $expectedTypes
     * @param ClassMetadata[] $bundlesMapping
     *
     * @dataProvider getExecuteData
     */
    public function testGetTypes($types, $expectedTypes, $bundlesMapping)
    {
        /** @var Manager|\PHPUnit_Framework_MockObject_MockObject $manager */
        $manager = $this->getMockBuilder('ONGR\ElasticsearchBundle\Service\Manager')
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects($this->exactly(2))
            ->method('getBundlesMapping')
            ->willReturn($bundlesMapping);

        $connection = $this->getMockBuilder('ONGR\ElasticsearchBundle\Client\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->once())
            ->method('search')
            ->with($expectedTypes, [], [])
            ->willReturn(['test']);

        $manager->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $search = new Search();

        $repository = new Repository($manager, $types);
        $results = $repository->execute($search, Repository::RESULTS_RAW);

        $this->assertEquals(['test'], $results);
    }

    /**
     * Returns class metadata mock.
     *
     * @param array $options
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ClassMetadata
     */
    private function getClassMetadata(array $options)
    {
        $mock = $this->getMockBuilder('ONGR\ElasticsearchBundle\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        foreach ($options as $name => $value) {
            $mock
                ->expects($this->any())
                ->method('get' . ucfirst($name))
                ->will($this->returnValue($value));
        }

        return $mock;
    }

    /**
     * Tests that getDocumentsClass method can can retrieve all FQNs.
     *
     * @param array           $types
     * @param array           $expectedTypes
     * @param ClassMetadata[] $bundlesMapping
     *
     * @dataProvider getExecuteData
     */
    public function testGetDocumentsClass($types, $expectedTypes, $bundlesMapping)
    {
        /** @var Manager|\PHPUnit_Framework_MockObject_MockObject $manager */
        $manager = $this->getMockBuilder('ONGR\ElasticsearchBundle\Service\Manager')
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects($this->any())
            ->method('getBundlesMapping')
            ->willReturn($bundlesMapping);

        $repository = new Repository($manager, $types);

        $classes = $repository->getDocumentsClass();

        foreach ($classes as $repositoryName => $class) {
            $this->assertEquals($bundlesMapping[$repositoryName]->getNamespace(), $class);
            unset($bundlesMapping[$repositoryName]);
        }
        $this->assertEmpty($bundlesMapping);
    }

    /**
     * Tests getDocumentsClass method with arguments.
     */
    public function testGetDocumentsClassArguments()
    {
        /** @var ClassMetadata[] $mapping */
        $mapping = [
            'AcmeTestBundle:Product' => $this->getClassMetadata(
                [
                    'type' => 'product',
                    'fields' => [],
                    'namespace' => 'Product',
                ]
            ),
            'AcmeTestBundle:Content' => $this->getClassMetadata(
                [
                    'type' => 'content',
                    'fields' => [],
                    'namespace' => 'Content',
                ]
            ),
            'AcmeTestBundle:Category' => $this->getClassMetadata(
                [
                    'type' => 'category',
                    'fields' => [],
                    'namespace' => 'Category',
                ]
            ),
            'AcmeTestBundle:Comment' => $this->getClassMetadata(
                [
                    'type' => 'comment',
                    'fields' => [],
                    'namespace' => 'Comment',
                ]
            ),
        ];

        /** @var Manager|\PHPUnit_Framework_MockObject_MockObject $manager */
        $manager = $this->getMockBuilder('ONGR\ElasticsearchBundle\Service\Manager')
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects($this->any())
            ->method('getBundlesMapping')
            ->willReturn($mapping);
        $repository = new Repository($manager, array_keys($mapping));

        $this->assertEquals('Product', $repository->getDocumentsClass(null));
        $this->assertEquals('Product', $repository->getDocumentsClass('AcmeTestBundle:Product'));
        $this->assertEquals('Category', $repository->getDocumentsClass('AcmeTestBundle:Category'));
        $this->assertEquals(
            ['AcmeTestBundle:Comment' => 'Comment'],
            $repository->getDocumentsClass(['AcmeTestBundle:Comment'])
        );
        $this->assertEquals(
            [
                'AcmeTestBundle:Comment' => 'Comment',
                'AcmeTestBundle:Content' => 'Content',
            ],
            $repository->getDocumentsClass(['AcmeTestBundle:Comment', 'AcmeTestBundle:Content'])
        );
        $this->assertEquals(
            [
                'AcmeTestBundle:Product' => 'Product',
                'AcmeTestBundle:Content' => 'Content',
                'AcmeTestBundle:Category' => 'Category',
                'AcmeTestBundle:Comment' => 'Comment',
            ],
            $repository->getDocumentsClass([])
        );
        $this->assertEquals($repository->getDocumentsClass([]), $repository->getDocumentsClass());
    }
}
