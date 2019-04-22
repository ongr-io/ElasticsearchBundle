<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\EventListener;

use ONGR\App\Document\DummyDocument;
use ONGR\App\Entity\DummyDocumentInTheEntityDirectory;
use ONGR\ElasticsearchBundle\EventListener\TerminateListener;
use ONGR\ElasticsearchBundle\Service\IndexService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * Tests TerminateListener class
 */
class TerminateListenerTest extends TestCase
{
    /**
     * Tests kernel terminate event
     */
    public function testKernelTerminate()
    {
        $indexService = $this->getMockBuilder(IndexService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $indexService->expects($this->exactly(2))
            ->method('commit');

        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->any())
            ->method('get')
            ->with($this->logicalOr(
                DummyDocument::class,
                DummyDocumentInTheEntityDirectory::class
            ))
            ->willReturn($indexService);

        $listener = new TerminateListener(
            $container,
            [
                DummyDocument::class,
                DummyDocumentInTheEntityDirectory::class,
            ]
        );

        $listener->onKernelTerminate();
    }
}
