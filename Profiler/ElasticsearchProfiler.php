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

    /**
     * @var Logger[] Watched loggers.
     */
    private $loggers = [];

    /**
     * @var array Queries array.
     */
    private $queries = [];

    /**
     * @var int Query count.
     */
    private $count = 0;

    /**
     * @var float Time all queries took.
     */
    private $time = .0;

    /**
     * @var array Registered managers.
     */
    private $managers = [];

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
                    $this->handleRecords($this->getRoute($request), $handler->getRecords());
                    $handler->clearRecords();
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->queries = [];
        $this->count = 0;
        $this->time = 0;
    }

    /**
     * Returns total time queries took.
     *
     * @return string
     */
    public function getTime()
    {
        return round($this->time * 1000, 2);
    }

    /**
     * Returns number of queries executed.
     *
     * @return int
     */
    public function getQueryCount()
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
    public function getQueries()
    {
        return $this->queries;
    }

    /**
     * @return array
     */
    public function getManagers()
    {
        if (is_array(reset($this->managers))) {
            foreach ($this->managers as $name => &$manager) {
                $manager = $name === 'default' ? 'es.manager' : sprintf('es.manager.%s', $name);
            }
        }

        return $this->managers;
    }

    /**
     * @param array $managers
     */
    public function setManagers($managers)
    {
        $this->managers = $managers;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ongr.profiler';
    }

    /**
     * Handles passed records.
     *
     * @param string $route
     * @param array  $records
     */
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

    /**
     * Adds query to collected data array.
     *
     * @param string $route
     * @param array  $record
     * @param string $queryBody
     */
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

    /**
     * Returns route name from request.
     *
     * @param Request $request
     *
     * @return string
     */
    private function getRoute(Request $request)
    {
        $route = $request->attributes->get('_route');

        return empty($route) ? self::UNDEFINED_ROUTE : $route;
    }
}
