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
use ONGR\ElasticsearchBundle\Document\AbstractDocument;
use ONGR\ElasticsearchBundle\Document\Suggestions;

/**
 * Document for suggester testing.
 * @ES\Document()
 */
class Suggester extends AbstractDocument
{
    /**
     * @var string
     * @ES\Property(type="string", name="title")
     */
    protected $title;

    /**
     * @var Suggestions
     * @ES\Suggester(name="completionSuggester", payloads=true)
     */
    protected $completion;

    /**
     * @var Suggestions
     * @ES\Suggester(
     *  name="contextSuggester",
     *  payloads=true,
     *  context={
     *      "title" : {"type" : "category"},
     *      "location" : {"type" : "geo", "precision" : "1km"}
     *  },
     * )
     */
    protected $context;

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Suggestions
     */
    public function getCompletion()
    {
        return $this->completion;
    }

    /**
     * @param Suggestions $completion
     *
     * @return $this
     */
    public function setCompletion($completion)
    {
        $this->completion = $completion;

        return $this;
    }

    /**
     * @return Suggestions
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param Suggestions $context
     *
     * @return $this
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }
}
