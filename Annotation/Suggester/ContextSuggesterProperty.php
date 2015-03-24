<?php

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ongr\ElasticsearchBundle\Annotation\Suggester;

use Ongr\ElasticsearchBundle\Annotation\Suggester\Context\AbstractContext;

/**
 * Class for context suggester annotations.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class ContextSuggesterProperty extends AbstractSuggesterProperty
{
    /**
     * @var array<\Ongr\ElasticsearchBundle\Annotation\Suggester\Context\AbstractContext>
     */
    public $context;

    /**
     * {@inheritdoc}
     */
    public function dump(array $exclude = [])
    {
        $data = parent::dump(['context']);

        /** @var AbstractContext $singleContext */
        foreach ($this->context as $singleContext) {
            $data['context'][$singleContext->name] = $singleContext->dump();
        }

        return $data;
    }
}
