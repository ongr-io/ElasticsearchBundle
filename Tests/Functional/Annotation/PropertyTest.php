<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Annotation;

use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\ElasticsearchBundle\Test\DelayedObjectWrapper;

/**
 * Property Test.
 */
class PropertyTest extends AbstractElasticsearchTestCase
{
    /**
     * Check if "ignore above" option is set as expected.
     */
    public function testDocumentMappingWithIgnoreAbove()
    {
        $manager = $this->getManager();
        $params = [
            'index' => $manager->getConnection()->getIndexName(),
            'type' => 'product',
            'field' => 'limited',
        ];
        $result = $manager->getConnection()->getClient()->indices()->getFieldMapping($params);
        $expectedMapping = [
            'limited' => [
                'ignore_above' => 20,
                'type' => 'string',
                'index' => 'not_analyzed',
            ],
        ];
        $this->assertEquals($expectedMapping, $result['ongr-esb-test']['mappings']['product']['limited']['mapping']);
    }

    /**
     * Check if "store" and "indexName" options are set as expected.
     */
    public function testDocumentMappingWithStore()
    {
        $manager = DelayedObjectWrapper::wrap($this->getManager());
        $params = [
            'index' => $manager->getConnection()->getIndexName(),
            'type' => 'product',
            'field' => 'stored',
        ];
        $result = $manager->getConnection()->getClient()->indices()->getFieldMapping($params);
        $expectedMapping = [
            'stored' => [
                'store' => true,
                'index_name' => 'ongr-esb-test',
                'type' => 'string',
            ],
        ];
        $this->assertEquals($expectedMapping, $result['ongr-esb-test']['mappings']['product']['stored']['mapping']);
    }

    /**
     * Check if "doc_values" option is set as expected.
     */
    public function testDocumentMappingWithDocValues()
    {
        $manager = DelayedObjectWrapper::wrap($this->getManager());
        $params = [
            'index' => $manager->getConnection()->getIndexName(),
            'type' => 'product',
            'field' => 'column_stride_fashioned',
        ];
        $result = $manager->getConnection()->getClient()->indices()->getFieldMapping($params);
        $expectedMapping = [
            'column_stride_fashioned' => [
                'doc_values' => true,
                'type' => 'string',
                'index' => 'not_analyzed',
            ],
        ];
        $this->assertEquals(
            $expectedMapping,
            $result['ongr-esb-test']['mappings']['product']['column_stride_fashioned']['mapping']
        );
    }

    /**
     * Check if "term_vector" option is set as expected.
     */
    public function testDocumentMappingWithTermVector()
    {
        $manager = DelayedObjectWrapper::wrap($this->getManager());
        $params = [
            'index' => $manager->getConnection()->getIndexName(),
            'type' => 'product',
            'field' => 'term_vector',
        ];
        $result = $manager->getConnection()->getClient()->indices()->getFieldMapping($params);
        $expectedMapping = [
            'term_vector' => [
                'term_vector' => 'with_positions_offsets',
                'type' => 'string',
            ],
        ];
        $this->assertEquals(
            $expectedMapping,
            $result['ongr-esb-test']['mappings']['product']['term_vector']['mapping']
        );
    }

    /**
     * Check if "null_value" option is set as expected.
     */
    public function testDocumentMappingWithNullValue()
    {
        $manager = DelayedObjectWrapper::wrap($this->getManager());
        $params = [
            'index' => $manager->getConnection()->getIndexName(),
            'type' => 'product',
            'field' => 'null_value',
        ];
        $result = $manager->getConnection()->getClient()->indices()->getFieldMapping($params);
        $expectedMapping = [
            'null_value' => [
                'null_value' => 'any',
                'type' => 'string',
            ],
        ];
        $this->assertEquals(
            $expectedMapping,
            $result['ongr-esb-test']['mappings']['product']['null_value']['mapping']
        );
    }

    /**
     * Check if "norms" option "enabled" is set as expected.
     */
    public function testDocumentMappingWithNormsDisabled()
    {
        $manager = DelayedObjectWrapper::wrap($this->getManager());
        $params = [
            'index' => $manager->getConnection()->getIndexName(),
            'type' => 'product',
            'field' => 'norms_disabled',
        ];
        $result = $manager->getConnection()->getClient()->indices()->getFieldMapping($params);
        $expectedMapping = [
            'norms_disabled' => [
                'norms' => [
                    'enabled' => false,
                ],
                'type' => 'string',
            ],
        ];
        $this->assertEquals(
            $expectedMapping,
            $result['ongr-esb-test']['mappings']['product']['norms_disabled']['mapping']
        );
    }

    /**
     * Check if "norms" option "loading" is set as expected.
     */
    public function testDocumentMappingWithNormsEager()
    {
        $manager = DelayedObjectWrapper::wrap($this->getManager());
        $params = [
            'index' => $manager->getConnection()->getIndexName(),
            'type' => 'product',
            'field' => 'norms_eager',
        ];
        $result = $manager->getConnection()->getClient()->indices()->getFieldMapping($params);
        $expectedMapping = [
            'norms_eager' => [
                'norms' => [
                    'loading' => 'eager',
                ],
                'type' => 'string',
            ],
        ];
        $this->assertEquals(
            $expectedMapping,
            $result['ongr-esb-test']['mappings']['product']['norms_eager']['mapping']
        );
    }

    /**
     * Data provider for testDocumentMappingWithIncludeInAll.
     *
     * @return array
     */
    public function getTestDocumentMappingWithIncludeInAllData()
    {
        $out = [];
        // Case #0: should be included.
        $out[] = ['field' => 'included_in_all', 'expected' => true];
        // Case #1: should not be included.
        $out[] = ['field' => 'excluded_from_all', 'expected' => false];

        return $out;
    }

    /**
     * Check if "include in all" option is set as expected.
     *
     * @param string $field
     * @param bool   $expected
     *
     * @dataProvider getTestDocumentMappingWithIncludeInAllData
     */
    public function testDocumentMappingWithIncludeInAll($field, $expected)
    {
        $manager = DelayedObjectWrapper::wrap($this->getManager());
        $params = [
            'index' => $manager->getConnection()->getIndexName(),
            'type' => 'color',
            'field' => $field,
        ];
        $result = $manager->getConnection()->getClient()->indices()->getFieldMapping($params);
        $expectedMapping = [
            $field => [
                'include_in_all' => $expected,
                'type' => 'string',
            ],
        ];
        $this->assertEquals($expectedMapping, $result['ongr-esb-test']['mappings']['color'][$field]['mapping']);
    }
}
