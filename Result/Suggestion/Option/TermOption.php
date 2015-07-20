<?php

namespace ONGR\ElasticsearchBundle\Result\Suggestion\Option;

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use ONGR\ElasticsearchBundle\Result\Suggestion\Option;

/**
 * Data holder for term option.
 */
class TermOption extends SimpleOption
{
    /**
     * @var int
     */
    private $freq;

    /**
     * Constructor.
     *
     * @param string $text
     * @param float  $score
     * @param int    $freq
     */
    public function __construct($text, $score, $freq)
    {
        $this->freq = $freq;
        parent::__construct($text, $score);
    }

    /**
     * Returns suggested text document frequency.
     *
     * @return int
     */
    public function getFreq()
    {
        return $this->freq;
    }
}
