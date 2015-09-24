<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\FooBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Document\AbstractDocument;

/**
 * Testing document for representing media.
 *
 * @ES\Document(type="customer");
 */
class CustomerDocument extends AbstractDocument
{
    /**
     * Test adding raw mapping.
     *
     * @var string
     *
     * @ES\Property(name="name", type="string", index="not_analyzed", raw={"null_value":"data"})
     */
    public $name;
}
