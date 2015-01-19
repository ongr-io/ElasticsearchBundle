<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\DataCollector;

use Monolog\Logger;
use ONGR\ElasticsearchBundle\Logger\Handler\CollectionHandler;
use ONGR\ElasticsearchBundle\Service\JsonFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

/**
 * Data collector for profiling elasticsearch bundle.
 */
class ElasticsearchDataCollector implements DataCollectorInterface
{
    const UNDEFINED_ROUTE = 'undefined_route';

    /**
     * @var Logger[]
     */
    private $loggers = [];

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var array
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
        return 'es';
    }

    /**
     * Handles passed records.
     *
     * @param string $route
     * @param array  $records
     */
    private function handleRecords($route, $records)
    {
        $this->incQueryCount(count($records) / 2);
        $queryBody = '';
        foreach ($records as $record) {
            // First record will never have context.
            if (!empty($record['context'])) {
                $this->addTime($record['context']['duration']);
                $this->addQuery($route, $record, $queryBody);
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
        $this->data['queries'][$route][] = array_merge(
            [
                'body' => JsonFormatter::prettify(trim($queryBody, "'")),
                'method' => $record['context']['method'],
                'httpParameters' => $httpParameters,
                'time' => $record['context']['duration'] * 100,
            ],
            array_diff_key(parse_url($record['context']['uri']), array_flip(['query']))
        );
    }

    /**
     * Increases query count.
     *
     * @param int $count
     */
    private function incQueryCount($count = 1)
    {
        if (!isset($this->data['count'])) {
            $this->data['count'] = 0;
        }

        $this->data['count'] += $count;
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
