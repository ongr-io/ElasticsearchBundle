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

/**
 * Property Test.
 */
class PropertyTest extends AbstractElasticsearchTestCase
{
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
        $manager = $this->getManager();
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
