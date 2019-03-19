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

use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\PhpFileCache;
use ONGR\App\Document\DummyDocument;
use ONGR\App\Document\IndexWithFieldsDataDocument;
use ONGR\App\Entity\DummyDocumentInTheEntityDirectory;
use ONGR\ElasticsearchBundle\EventListener\TerminateListener;
use ONGR\ElasticsearchBundle\Mapping\Converter;
use ONGR\ElasticsearchBundle\Mapping\DocumentParser;
use ONGR\ElasticsearchBundle\Profiler\ElasticsearchProfiler;
use ONGR\ElasticsearchBundle\Service\IndexService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ElasticsearchBundleExtensionTest extends KernelTestCase
{
    /**
     * @return array
     */
    public function getTestContainerData()
    {
        return [
            [
                'ongr.esb.cache',
                PhpFileCache::class,
            ],
            [
                'ongr.esb.cache_reader',
                CachedReader::class,
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

            //This tests if a service swap works well
            [
                DummyDocument::class,
                IndexService::class,
            ],
            [
                DummyDocumentInTheEntityDirectory::class,
                IndexService::class,
            ],
            [
                IndexWithFieldsDataDocument::class,
                IndexService::class,
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
        $this->assertInstanceOf(
            $instance,
            $container->get($id),
            sprintf('The instance %s type is not as expected.', $id)
        );
    }
}
