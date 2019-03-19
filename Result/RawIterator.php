<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Result;

/**
 * A simple iterator which returns the raw result it got from the elasticsearch.
 */
class RawIterator extends AbstractResultsIterator
{
    protected function convertDocument(array $raw)
    {
        return $raw;
    }
}
