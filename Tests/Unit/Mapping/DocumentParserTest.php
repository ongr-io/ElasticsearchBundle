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
use ONGR\App\Document\DummyDocument;
use ONGR\App\Entity\DummyDocumentInTheEntityDirectory;
use ONGR\ElasticsearchBundle\Mapping\DocumentParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class DocumentParserTest extends TestCase
{
    public function testDocumentParsing()
    {

        $parser = new DocumentParser(new AnnotationReader(), $this->createMock(Cache::class));

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

    public function testMultiFieldsMapping()
    {
        $parser = new DocumentParser(new AnnotationReader(), $this->createMock(Cache::class));

        $indexMetadata = $parser->getIndexMetadata(new \ReflectionClass(DummyDocument::class));

        // Mapping definition for field "title" should be there
        $this->assertNotEmpty($indexMetadata['mappings']['_doc']['properties']['title']);
        $title_field_def = $indexMetadata['mappings']['_doc']['properties']['title'];

        // title should have `fields` sub-array
        $this->assertArrayHasKey('fields', $title_field_def);

        // `fields` should look like so:
        $expected = [
            'raw' => ['type' => 'keyword'],
            'increment' => ['type' => 'text', 'analyzer' => 'incrementalAnalyzer']
        ];

        $this->assertEquals($expected, $title_field_def['fields']);
    }

    public function testGetAnalysisConfig()
    {
        $config = Yaml::parseFile(__DIR__ . '/../../app/config/config_test.yml');
        $config_analysis = $config['ongr_elasticsearch']['analysis'];

        $parser = new DocumentParser(new AnnotationReader(), $this->createMock(Cache::class), $config_analysis);
        $analysis = $parser->getAnalysisConfig(new \ReflectionClass(DummyDocument::class));

        $expected = [
            'analyzer' => [
                'incrementalAnalyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => [
                            0 => 'lowercase',
                            1 => 'edge_ngram_filter',
                        ],
                    ],
                ],
            'filter' => [
                'edge_ngram_filter' => [
                    'type' => 'edge_ngram',
                    'min_gram' => 1,
                    'max_gram' => 20,
                ],
            ],
        ];

        $this->assertEquals($expected, $analysis);
    }
}
