<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Service;

use ONGR\ElasticsearchBundle\Mapping\MetadataCollector;
use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\DummyDocument;

class RepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Data provider for testConstructorException().
     *
     * @return array
     */
    public function getTestConstructorExceptionData()
    {
        return [
            [
                12345,
                'InvalidArgumentException',
            ],
            [
                'Non\Existing\ClassName',
                'InvalidArgumentException',
            ],
        ];
    }

    /**
     * @param $className
     * @param $expectedExceptionMessage
     *
     * @dataProvider getTestConstructorExceptionData()
     */
    public function testConstructorException($className, $expectedExceptionMessage)
    {
        $this->expectException($expectedExceptionMessage);

        new Repository(null, $className);
    }

    /**
     * Tests class getter
     */
    public function testGetRepositoryClass()
    {
        $collector = $this->getMockBuilder(MetadataCollector::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collector->expects($this->any())->method('getDocumentType')->willReturn('product');
        $manager = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects($this->any())->method('getMetadataCollector')->willReturn($collector);
        $repository = new Repository(
            $manager,
            DummyDocument::class
        );
        $this->assertEquals(
            DummyDocument::class,
            $repository->getClassName()
        );
    }
}
