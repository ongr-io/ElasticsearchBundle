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
 * Class RawResultIterator.
 */
class RawResultIterator extends AbstractResultsIterator implements \Iterator, \Countable, \ArrayAccess
{
    use IteratorTrait;
    use CountableTrait;
    use ArrayAccessTrait;
}
