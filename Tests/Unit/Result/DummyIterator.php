<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Result;

use ONGR\ElasticsearchBundle\Result\AbstractResultsIterator;

class DummyIterator extends AbstractResultsIterator
{
    /**
     * {@inheritdoc}
     */
    protected function convertDocument(array $document)
    {
        return $document;
    }
}
