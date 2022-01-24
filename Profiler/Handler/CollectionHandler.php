<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Profiler\Handler;

use Monolog\Handler\AbstractProcessingHandler;

/**
 * Handler that saves all records to him self.
 */
class CollectionHandler extends AbstractProcessingHandler
{
    /**
     * @var array
     */
    private array $records = [];

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        $this->records[] = $record;
    }

    /**
     * Returns recorded data.
     *
     * @return array
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * Clears recorded data.
     */
    public function clearRecords(): void
    {
        $this->records = [];
    }
}
