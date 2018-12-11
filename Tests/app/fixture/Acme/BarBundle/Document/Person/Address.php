<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Person;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Category document for testing.
 *
 * @ES\ObjectType
 */
class Address
{
    /**
     * @var string
     * @ES\Property(type="string")
     */
    public $city;

    /**
     * @var string
     * @ES\Property(type="string")
     */
    public $state;
}
