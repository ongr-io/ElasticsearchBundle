<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Product document for testing.
 *
 * @ES\Document(type="person")
 */
class Person
{
    /**
     * @var string
     *
     * @ES\Property(type="string")
     */
    public $firstName;

    /**
     * @var string
     *
     * @ES\Property(type="string", name="family_name")
     */
    public $lastName;

    /**
     * @var int
     *
     * @ES\Property(type="integer")
     */
    public $age;
}
