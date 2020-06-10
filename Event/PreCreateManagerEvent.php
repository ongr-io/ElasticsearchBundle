<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Event;

use Elasticsearch\ClientBuilder;

class PreCreateManagerEvent extends BaseEvent
{
    /**
     * @var ClientBuilder
     */
    private $client;

    /**
     * @var array
     */
    private $indexSettings;

    /**
     * CreateManagerEvent constructor.
     *
     * @param ClientBuilder $client
     * @param $indexSettings array
     */
    public function __construct(ClientBuilder $client, &$indexSettings)
    {
        $this->client = $client;
        $this->indexSettings = $indexSettings;
    }

    /**
     * @return ClientBuilder
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param ClientBuilder $client
     */
    public function setClient(ClientBuilder $client)
    {
        $this->client = $client;
    }

    /**
     * @return array
     */
    public function getIndexSettings()
    {
        return $this->indexSettings;
    }

    /**
     * @param array $indexSettings
     */
    public function setIndexSettings($indexSettings)
    {
        $this->indexSettings = $indexSettings;
    }
}
