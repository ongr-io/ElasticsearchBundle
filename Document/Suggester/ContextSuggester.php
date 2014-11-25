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
 * Class to be used for context suggestion objects.
 *
 * @ES\Object
 */
class ContextSuggester extends AbstractSuggester
{
    private $contexts;

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'completion';
    }
}
