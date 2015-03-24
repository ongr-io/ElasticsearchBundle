<?php

namespace Ongr\ElasticsearchBundle\Result\Suggestion\Option;

/*
 * This file is part of the Ongr package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Ongr\ElasticsearchBundle\Result\Suggestion\Option;

/**
 * Data holder for completion option, used by context and completion suggesters.
 */
class CompletionOption extends SimpleOption
{
    /**
     * @var array
     */
    private $payload;

    /**
     * Constructor.
     *
     * @param string $text
     * @param float  $score
     * @param array  $payload
     */
    public function __construct($text, $score, $payload)
    {
        $this->payload = $payload;
        parent::__construct($text, $score);
    }

    /**
     * Returns payload data.
     *
     * @return string
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
