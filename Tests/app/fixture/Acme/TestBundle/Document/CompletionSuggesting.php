<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\Acme\TestBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;
use ONGR\ElasticsearchBundle\Document\Suggester\AbstractSuggester;
use ONGR\ElasticsearchBundle\Document\Suggester\CompletionSuggesterInterface;

/**
 * Suggesting document for testing.
 *
 * @ES\Object()
 */
class CompletionSuggesting extends AbstractSuggester implements CompletionSuggesterInterface
{
}
