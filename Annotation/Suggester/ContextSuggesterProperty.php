<?php

namespace ONGR\ElasticsearchBundle\Annotation\Suggester;

use ONGR\ElasticsearchBundle\Annotation\Suggester\Context\AbstractContext;

/**
 * Class for context suggester annotations.
 *
 * @Annotation
 * @Target("PROPERTY")
 * @Attributes({
 *     @Attribute(
 *        "context",
 *        type = "array<\ONGR\ElasticsearchBundle\Annotation\Suggester\Context\AbstractContext>",
 *        required = true
 *     ),
 * })
 */
class ContextSuggesterProperty extends AbstractSuggesterProperty
{
    /**
     * Constructor for lowercase settings.
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->context = $values['context'];
        parent::__construct($values);
    }

    /**
     * @var AbstractContext[]
     */
    public $context;

    /**
     * {@inheritdoc}
     */
    public function filter($extraExclude = [])
    {
        $data = parent::filter(['context']);

        /** @var AbstractContext $singleContext */
        foreach ($this->context as $singleContext) {
            $data['context'][$singleContext->name] = $singleContext->filter();
        }

        return $data;
    }
}
