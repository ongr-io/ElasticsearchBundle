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
use Doctrine\Common\Cache\Cache;
use ONGR\App\Entity\DummyDocumentInTheEntityDirectory;
use ONGR\ElasticsearchBundle\Mapping\DocumentParser;
use PHPUnit\Framework\TestCase;

class DocumentParserTest extends TestCase
{
    public function testDocumentParsing()
    {

        $parser = new DocumentParser(new AnnotationReader(), $this->createMock(Cache::class));
        ;

        $indexMetadata = $parser->getIndexMetadata(new \ReflectionClass(DummyDocumentInTheEntityDirectory::class));

        $expected = [
            'mappings' => [
                '_doc' => [
                    'properties' => [
                            'keyword_field' => [
                            'type' => 'keyword',
                            ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $indexMetadata);
    }
}
