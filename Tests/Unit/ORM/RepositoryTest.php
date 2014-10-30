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

use ONGR\ElasticsearchBundle\DSL\Search;
use ONGR\ElasticsearchBundle\ORM\Repository;

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

        $bundlesMapping = [
            'AcmeTestBundle:Product' => ['type' => 'product', 'fields' => []],
            'AcmeTestBundle:Content' => ['type' => 'content', 'fields' => []],
        ];

        // Case #0 Single type.
        $out[] = [['AcmeTestBundle:Product'], ['product'], $bundlesMapping];

        // Case #1 Multi types.
        $out[] = [[], ['product', 'content'], $bundlesMapping];

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
        $manager = $this->getMockBuilder('ONGR\ElasticsearchBundle\ORM\Manager')
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
}
