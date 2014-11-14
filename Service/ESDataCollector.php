<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Service;

use Monolog\Logger;
use ONGR\ElasticsearchBundle\Logger\Handler\CollectionHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

/**
 * Data collector for profiling elasticsearch bundle.
 */
class ESDataCollector implements DataCollectorInterface
{
    /**
     * @var Logger[]
     */
    private $loggers = [];

    /**
     * @var array
     */
    private $data = [];

    /**
     * Adds logger to look for collector handler.
     *
     * @param Logger $logger
     */
    public function addLogger(Logger $logger)
    {
        $this->loggers[] = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        /** @var Logger $logger */
        foreach ($this->loggers as $logger) {
            foreach ($logger->getHandlers() as $handler) {
                if ($handler instanceof CollectionHandler) {
                    $this->handleRecords($handler->getRecords());
                }
            }
        }
    }

    /**
     * Returns total time queries took.
     *
     * @return string
     */
    public function getTime()
    {
        return round($this->data['time'] * 100, 2);
    }

    /**
     * Returns number of queries executed.
     *
     * @return int
     */
    public function getQueryCount()
    {
        return $this->data['count'];
    }

    /**
     * Returns information about executed queries.
     *
     * Eg. keys:
     *      'body'    - Request body.
     *      'method'  - HTTP method.
     *      'uri'     - Uri request was sent.
     *      'time'    - Time client took to respond.
     *
     * @return array
     */
    public function getQueries()
    {
        return $this->data['queries'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'es';
    }

    /**
     * Handles passed records.
     *
     * @param array $records
     */
    private function handleRecords($records)
    {
        $this->data['count'] = count($records) / 2;

        foreach ($records as $record) {
            // First record will never have context.
            if (!empty($record['context'])) {
                $this->addTime($record['context']['duration']);
                $this->data['queries'][] = [
                    'body' => trim($queryBody, "'"),
                    'method' => $record['context']['method'],
                    'uri' => $record['context']['uri'],
                    'time' => $record['context']['duration'] * 100,
                ];
            } else {
                $position = strpos($record['message'], '-d');
                $queryBody = $position !== false ? substr($record['message'], $position + 3) : '';
            }
        }
    }

    /**
     * Adds time to total.
     *
     * @param float $time
     */
    private function addTime($time)
    {
        if (!isset($this->data['time'])) {
            $this->data['time'] = .0;
        }

        $this->data['time'] += $time;
    }
}
