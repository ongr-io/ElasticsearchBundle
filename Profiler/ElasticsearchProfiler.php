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
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\VarDumper\Caster\CutStub;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\Stub;
use Symfony\Component\VarDumper\Cloner\VarCloner;

/**
 * Data collector for profiling elasticsearch bundle.
 */
class ElasticsearchProfiler extends DataCollector
{
    const UNDEFINED_ROUTE = 'undefined_route';

    /**
     * @var Logger[] Watched loggers.
     */
    private $loggers = [];

    public function __construct()
    {
        $this->reset();
    }

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
        $this->data = [
            'managers' => [],
            'queries' => [],
            'count' => 0,
            'time' => .0,
        ];
    }

    /**
     * Returns total time queries took.
     *
     * @return string
     */
    public function getTime()
    {
        return round($this->data['time'] * 1000, 2);
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
        return $this->cloneVar($this->data['queries']);
    }

    /**
     * @return array
     */
    public function getManagers()
    {
        $viewManagers = [];
        foreach ($this->data['managers'] as $name => $manager) {
            $viewManagers[$name] = $this->cloneVar(
                $name === 'default'
                    ? 'es.manager'
                    : sprintf('es.manager.%s', $name)
            );
        }

        return $viewManagers;
    }

    /**
     * @param array $managers
     */
    public function setManagers(array $managers)
    {
        $this->data['managers'] = $managers;
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
        $this->data['count'] += count($records) / 2;
        $queryBody = '';
        foreach ($records as $record) {
            // First record will never have context.
            if (!empty($record['context'])) {
                $this->data['time'] += $record['context']['duration'];
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
        $this->data['queries'][$route][] = array_merge(
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
