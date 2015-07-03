<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\DSL;

/**
 * A trait which handles elasticsearch aggregation script.
 */
trait ScriptAwareTrait
{
    /**
     * @var string
     */
    private $script;

    /**
     * @var array
     */
    private $params;

    /**
     * @return string
     */
    public function getScript()
    {
        return $this->script;
    }

    /**
     * @param string $script
     */
    public function setScript($script)
    {
        $this->script = $script;
    }

    /**
     * @param $params
     */
    public function setScriptParams($params)
    {
        $this->params = $params;
    }

    /**
     * @return array
     */
    public function getScriptParams()
    {
        return $this->params;
    }
}
