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
use ONGR\App\Document\TestDocument;
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

    public function testParsingWithMultiFieldsMapping()
    {
        $parser = new DocumentParser(new AnnotationReader(), $this->createMock(Cache::class));

        $indexMetadata = $parser->getIndexMetadata(new \ReflectionClass(TestDocument::class));

        // Mapping definition for field "title" should be there
        $this->assertNotEmpty($indexMetadata['mappings']['_doc']['properties']['title']);
        $title_field_def = $indexMetadata['mappings']['_doc']['properties']['title'];

        // title should have `fields` sub-array
        $this->assertArrayHasKey('fields', $title_field_def);

        // `fields` should look like so:
        $expected = [
            'raw' => ['type' => 'keyword'],
            'increment' => ['type' => 'text', 'analyzer' => 'incrementalAnalyzer'],
            'sorting' => ['type' => 'keyword', 'normalizer' => 'lowercase_normalizer']
        ];

        $this->assertEquals($expected, $title_field_def['fields']);
    }

    public function testGetAnalysisConfig()
    {
        // Global analysis settings used for this test, usually set in the bundle configuration
        // sets custom analyzer, filter, and normalizer
        $config_analysis = [
            'analyzer' => [
                'incrementalAnalyzer' => [
                    'type' => 'custom',
                    'tokenizer' => 'standard',
                    'filter' => [
                        0 => 'lowercase',
                        1 => 'edge_ngram_filter',
                    ],
                ],
                'unusedAnalyzer' => [
                    'type' => 'custom',
                    'tokenizer' => 'standard'
                ]
            ],
            'filter' => [
                'edge_ngram_filter' => [
                    'type' => 'edge_ngram',
                    'min_gram' => 1,
                    'max_gram' => 20,
                ],
            ],
            'normalizer' => [
                'lowercase_normalizer' => [
                    'type' => 'custom',
                    'filter' => ['lowercase']
                ],
                'unused_normalizer' => [
                    'type' => 'custom'
                ]
            ]
        ];

        $parser = new DocumentParser(new AnnotationReader(), $this->createMock(Cache::class), $config_analysis);
        $analysis = $parser->getAnalysisConfig(new \ReflectionClass(TestDocument::class));

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
                // 'unusedAnalyzer' must not be there because it is not used
            'filter' => [
                'edge_ngram_filter' => [
                    'type' => 'edge_ngram',
                    'min_gram' => 1,
                    'max_gram' => 20,
                ],
            ],
            'normalizer' => [
                'lowercase_normalizer' => [
                    'type' => 'custom',
                    'filter' => ['lowercase']
                ]
                // 'unused_normalizer' must not be there
            ]
        ];

        $this->assertEquals($expected, $analysis);
    }
}
