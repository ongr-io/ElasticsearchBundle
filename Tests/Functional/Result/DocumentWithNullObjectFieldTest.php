<?php
/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Result;

use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class DocumentNullObjectFieldTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getDataArray()
    {
        return [
            'default' => [
                'place' => [
                    [
                        '_id' => 'foo',
                        'title' => 'Bar Product',
                        'address' => null,
                    ],
                ],
            ],
        ];
    }

    /**
     * Test if fetched object field is actually NULL.
     */
    public function testResultWithNullObjectField()
    {
        $repository = $this->getManager()->getRepository('AcmeBarBundle:Place');
        $document = $repository->find('foo');

        $this->assertInstanceOf(
            'ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Place',
            $document
        );

        $this->assertNull($document->getAddress());
    }
}
