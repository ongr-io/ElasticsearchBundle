<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use ONGR\ElasticsearchBundle\Mapping\DocumentFinder;
use ONGR\ElasticsearchBundle\Mapping\DocumentParser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DocumentParserTest extends WebTestCase
{
    /**
     * Test if exception is thrown when Document is used as embeddable.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage should have @ObjectType or @Nested annotation
     */
    public function testGetDocumentTypeException()
    {
        $container = $this->createClient()->getContainer();

        $reader = new AnnotationReader();
        $finder = new DocumentFinder($container->getParameter('kernel.bundles'));

        $parser = new DocumentParser($reader, $finder);
        $parser->parse(new \ReflectionClass(__NAMESPACE__ . '\InvalidDocument'));
    }
}
