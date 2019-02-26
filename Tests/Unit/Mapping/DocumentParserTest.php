<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use ONGR\ElasticsearchBundle\Annotation\Id;
use ONGR\ElasticsearchBundle\Annotation\Index;
use ONGR\ElasticsearchBundle\Mapping\DocumentParser;
use ONGR\ElasticsearchBundle\Service\IndexService;

class DocumentParserTest extends \PHPUnit\Framework\TestCase
{
    public function testDocumentParsing()
    {
        $namespace = 'ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document\DummyDocument';
        $parser = new DocumentParser(new AnnotationReader());;

        $indexMetadata = $parser->getIndexMetadata($namespace);

        $this->assertEquals([], $indexMetadata);
    }
}
