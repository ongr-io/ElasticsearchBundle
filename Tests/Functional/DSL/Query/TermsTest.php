<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\DSL\Query;

use ONGR\ElasticsearchBundle\DSL\Query\TermsQuery;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Test\ElasticsearchTestCase;

class TermsTest extends ElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getDataArray()
    {
        return [
            'default' => [
                'product' => [
                    [
                        '_id' => 1,
                        'title' => 'foo',
                    ],
                    [
                        '_id' => 2,
                        'title' => 'foo baz',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testTermsQuery().
     *
     * @return array
     */
    public function getTestTermsQueryData()
    {
        $out = [];

        $testProducts = $this->getDataArray()['default']['product'];

        foreach ($testProducts as &$record) {
            unset($record['_id']);
        }

        // Case #0 at least one should match - return all products.
        $out[] = [['foo', 'baz'], ['boost' => 1.0], array_reverse($testProducts)];

        // Case #1 both tags must match - returns only second product.
        $out[] = [['foo', 'baz'], ['minimum_should_match' => 2], [$testProducts[1]]];

        return $out;
    }

    /**
     * Test terms query for expected search result.
     *
     * @param array $tags
     * @param array $parameters
     * @param array $expected
     *
     * @dataProvider getTestTermsQueryData
     */
    public function testTermsQuery($tags, $parameters, $expected)
    {
        /** @var Repository $repo */
        $repo = $this->getManager()->getRepository('AcmeTestBundle:Product');

        $termsQuery = new TermsQuery('title', $tags, $parameters);

        $search = $repo->createSearch()->addQuery($termsQuery);

        $results = $repo->execute($search, Repository::RESULTS_ARRAY);

        $this->assertEquals($expected, $results);
    }
}
