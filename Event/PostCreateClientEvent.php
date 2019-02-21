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
use Symfony\Component\EventDispatcher\Event;

class PostCreateClientEvent extends Event
{
    private $client;
    private $indexConfig;

    public function __construct(ClientBuilder $client, array $indexConfig = [])
    {
        $this->client = $client;
        $this->indexConfig = $indexConfig;
    }

    public function getClient(): ClientBuilder
    {
        return $this->client;
    }

    public function setClient(ClientBuilder $client): PostCreateClientEvent
    {
        $this->client = $client;
        return $this;
    }

    public function getIndexConfig(): array
    {
        return $this->indexConfig;
    }

    public function setIndexConfig(array $indexConfig): PostCreateClientEvent
    {
        $this->indexConfig = $indexConfig;
        return $this;
    }
}
