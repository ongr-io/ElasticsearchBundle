<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\Unit\ORM;

use Ongr\ElasticsearchBundle\DSL\Search;
use Ongr\ElasticsearchBundle\Mapping\ClassMetadata;
use Ongr\ElasticsearchBundle\ORM\Repository;

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

        // Case #0 Single type.
        $out[] = [
            ['AcmeTestBundle:Product'],
            ['product'],
            [
                'AcmeTestBundle:Product' => $this->getClassMetadata(
                    [
                        'type' => 'product',
                        'fields' => [],
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
                    ]
                ),
                'AcmeTestBundle:Content' => $this->getClassMetadata(
                    [
                        'type' => 'content',
                        'fields' => [],
                    ]
                ),
            ],
        ];

        return $out;
    }

    /**
     * Test for getTypes().
     *
     * @param array $types
     * @param array $expectedTypes
     * @param array $bundlesMapping
     *
     * @dataProvider getExecuteData
     */
    public function testGetTypes($types, $expectedTypes, $bundlesMapping)
    {
        $manager = $this->getMockBuilder('Ongr\ElasticsearchBundle\ORM\Manager')
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects($this->exactly(2))
            ->method('getBundlesMapping')
            ->willReturn($bundlesMapping);

        $connection = $this->getMockBuilder('Ongr\ElasticsearchBundle\Client\Connection')
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
        $mock = $this->getMockBuilder('Ongr\ElasticsearchBundle\Mapping\ClassMetadata')
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
}
