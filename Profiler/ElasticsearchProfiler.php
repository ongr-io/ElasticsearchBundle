<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Profiler;

use Monolog\Logger;
use ONGR\ElasticsearchBundle\Profiler\Handler\CollectionHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

/**
 * Data collector for profiling elasticsearch bundle.
 */
class ElasticsearchProfiler implements DataCollectorInterface
{
    const UNDEFINED_ROUTE = 'undefined_route';

    private $loggers = [];
    private $queries = [];
    private $count = 0;
    private $time = .0;
    private $indexes = [];

    public function addLogger(Logger $logger)
    {
        $this->loggers[] = $logger;
    }

    public function setIndexes(array $indexes): void
    {
        $this->indexes = $indexes;
    }

    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
        /** @var Logger $logger */
        foreach ($this->loggers as $logger) {
            foreach ($logger->getHandlers() as $handler) {
                if ($handler instanceof CollectionHandler) {
                    $this->handleRecords($this->getRoute($request), $handler->getRecords());
                    $handler->clearRecords();
                }
            }
        }
    }

    public function reset()
    {
        $this->queries = [];
        $this->count = 0;
        $this->time = 0;
    }

    public function getTime(): float
    {
        return round($this->time * 1000, 2);
    }

    public function getQueryCount(): int
    {
        return $this->count;
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
    public function getQueries(): array
    {
        return $this->queries;
    }

    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function getName()
    {
        return 'ongr.profiler';
    }

    private function handleRecords($route, $records)
    {
        $this->count += count($records) / 2;
        $queryBody = '';
        foreach ($records as $record) {
            // First record will never have context.
            if (!empty($record['context'])) {
                $this->time += $record['context']['duration'];
                $this->addQuery($route, $record, $queryBody);
            } else {
                $position = strpos($record['message'], ' -d');
                $queryBody = $position !== false ? substr($record['message'], $position + 3) : '';
            }
        }
    }

    private function addQuery($route, $record, $queryBody)
    {
        parse_str(parse_url($record['context']['uri'], PHP_URL_QUERY), $httpParameters);
        $body = json_decode(trim($queryBody, " '\r\t\n"));
        $this->queries[$route][] = array_merge(
            [
                'body' => $body !== null ? json_encode($body, JSON_PRETTY_PRINT) : '',
                'method' => $record['context']['method'],
                'httpParameters' => $httpParameters,
                'time' => $record['context']['duration'] * 1000,
            ],
            array_diff_key(parse_url($record['context']['uri']), array_flip(['query']))
        );
    }

    private function getRoute(Request $request)
    {
        $route = $request->attributes->get('_route');

        return empty($route) ? self::UNDEFINED_ROUTE : $route;
    }
}
