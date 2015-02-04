<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Document\Suggester;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * Trait to be used for completion suggestion objects.
 *
 * @deprecated use ONGR\ElasticsearchBundle\Document\Suggester\AbstractSuggester
 * and implement CompletionSuggesterInterface, will be removed in 1.0.
 */
trait CompletionSuggesterTrait
{
    use SuggesterTrait;
}
