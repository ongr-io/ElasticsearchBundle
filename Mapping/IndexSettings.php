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
    private $indexName;
    private $alias;
    private $indexMetadata;
    private $propertyMetadata;
    private $hosts;
    private $defaultIndex = false;

    public function __construct(
        string $namespace,
        string $indexName,
        string $alias,
        array $indexMetadata = [],
        array $propertyMetadata = [],
        array $hosts = [],
        bool $defaultIndex = false
    ) {
        $this->namespace = $namespace;
        $this->indexName = $indexName;
        $this->alias = $alias;
        $this->indexMetadata = $indexMetadata;
        $this->propertyMetadata = $propertyMetadata;
        $this->hosts = $hosts;
        $this->defaultIndex = $defaultIndex;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function setNamespace($namespace): void
    {
        $this->namespace = $namespace;
    }

    public function getIndexName()
    {
        return $this->indexName;
    }

    public function setIndexName($indexName): void
    {
        $this->indexName = $indexName;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function setAlias($alias): void
    {
        $this->alias = $alias;
    }

    public function getIndexMetadata()
    {
        return $this->indexMetadata;
    }

    public function setIndexMetadata($indexMetadata): void
    {
        $this->indexMetadata = array_filter(array_merge_recursive(
            $this->indexMetadata,
            $indexMetadata
        ));
    }

    public function getPropertyMetadata(): array
    {
        return $this->propertyMetadata;
    }

    public function setPropertyMetadata(array $propertyMetadata): void
    {
        $this->propertyMetadata = $propertyMetadata;
    }

    public function getHosts()
    {
        return $this->hosts;
    }

    public function setHosts($hosts): void
    {
        $this->hosts = $hosts;
    }

    public function isDefaultIndex(): bool
    {
        return $this->defaultIndex;
    }

    public function setDefaultIndex(bool $defaultIndex): void
    {
        $this->defaultIndex = $defaultIndex;
    }
}
