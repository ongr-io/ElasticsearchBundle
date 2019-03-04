<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DependencyInjection;

use ONGR\ElasticsearchBundle\EventListener\TerminateListener;
use ONGR\ElasticsearchBundle\Mapping\Converter;
use ONGR\ElasticsearchBundle\Mapping\DocumentParser;
use ONGR\ElasticsearchBundle\Profiler\ElasticsearchProfiler;
use ONGR\ElasticsearchBundle\Service\ExportService;
use ONGR\ElasticsearchBundle\Service\ImportService;
use ONGR\ElasticsearchBundle\Service\IndexSuffixFinder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ElasticsearchBundleExtensionTest extends KernelTestCase
{
    /**
     * @return array
     */
    public function getTestContainerData()
    {
        return [
//            [
//                ImportService::class,
//                ImportService::class,
//            ],
//            [
//                ExportService::class,
//                ExportService::class,
//            ],
//            [
//                IndexSuffixFinder::class,
//                IndexSuffixFinder::class,
//            ],
            [
                'ongr.esb.cache',
                'Doctrine\Common\Cache\FilesystemCache',
            ],
            [
                'ongr.es.cache_reader',
                'Doctrine\Common\Annotations\CachedReader',
            ],
            [
                DocumentParser::class,
                DocumentParser::class,
            ],
            [
                Converter::class,
                Converter::class,
            ],
            [
                ElasticsearchProfiler::class,
                ElasticsearchProfiler::class,
            ],
            [
                TerminateListener::class,
                TerminateListener::class,
            ],
        ];
    }

    /**
     * Tests if container has all services.
     *
     * @param string $id
     * @param string $instance
     *
     * @dataProvider getTestContainerData
     */
    public function testContainer($id, $instance)
    {
        self::bootKernel();

        $container = self::$kernel->getContainer();

        $this->assertTrue($container->has($id), sprintf('Container don\'t have %s service.', $id));
        $this->assertInstanceOf($instance, $container->get($id), sprintf('The instance %s type is not as expected.', $id));
    }
}
