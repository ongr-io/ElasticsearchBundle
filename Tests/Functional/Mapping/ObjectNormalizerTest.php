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

use ONGR\App\Document\DummyDocument;
use ONGR\ElasticsearchBundle\Mapping\ObjectNormalizer;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;

class ObjectNormalizerTest extends AbstractElasticsearchTestCase
{
    public function testAllowedAttributes()
    {
        /** @var ObjectNormalizer $normalizer */
        $normalizer = $this->getContainer()->get(ObjectNormalizer::class);

        $documentArray = $normalizer->normalize(new DummyDocument(), 'array');

        $this->assertArrayNotHasKey('notMappedValue', $documentArray);
    }
}
