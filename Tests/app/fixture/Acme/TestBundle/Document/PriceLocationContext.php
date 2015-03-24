<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document;

use Ongr\ElasticsearchBundle\Annotation as ES;

/**
 * SuggestingContext document for testing.
 *
 * @ES\Object()
 */
class PriceLocationContext
{
    /**
     * @var string
     *
     * @ES\Property(name="price", type="string")
     */
    public $price;

    /**
     * @var array
     *
     * @ES\Property(name="location", type="string")
     */
    public $location;
}
