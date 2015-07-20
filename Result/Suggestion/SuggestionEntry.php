<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Result\Suggestion;

/**
 * Suggestions results holder.
 */
class SuggestionEntry
{
    /**
     * @var OptionIterator
     */
    private $options;

    /**
     * @var string
     */
    private $text;

    /**
     * @var int
     */
    private $offset;

    /**
     * @var int
     */
    private $length;

    /**
     * Constructor.
     *
     * @param string         $text
     * @param int            $offset
     * @param int            $length
     * @param OptionIterator $options
     */
    public function __construct($text, $offset, $length, OptionIterator $options)
    {
        $this->text = $text;
        $this->offset = $offset;
        $this->length = $length;
        $this->options = $options;
    }

    /**
     * Return original length.
     *
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Returns original start offset.
     *
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Returns original text.
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Returns suggested options.
     *
     * @return OptionIterator
     */
    public function getOptions()
    {
        return $this->options;
    }
}
