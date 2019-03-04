<?php
/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Mapping;

class IndexSettings
{
    private $namespace;

    private $alias;

    /**
     * @deprecated will be removed in the v7
     */
    private $type;

    private $hosts = [];

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setNamespace(string $namespace): IndexSettings
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): IndexSettings
    {
        $this->type = $type;
        return $this;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): IndexSettings
    {
        $this->alias = $alias;
        return $this;
    }

    public function getHosts(): array
    {
        return $this->hosts;
    }

    public function setHosts(array $hosts): IndexSettings
    {
        $this->hosts = $hosts;
        return $this;
    }
}