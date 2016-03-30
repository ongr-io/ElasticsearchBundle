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

use Elasticsearch\Helper\Iterators\SearchHitIterator;
use Elasticsearch\Helper\Iterators\SearchResponseIterator;
use ONGR\ElasticsearchBundle\Service\Json\JsonWriter;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ExportService class.
 */
class ExportService
{
    /**
     * Exports es index to provided file.
     *
     * @param Manager         $manager
     * @param string          $filename
     * @param array           $types
     * @param int             $chunkSize
     * @param OutputInterface $output
     */
    public function exportIndex(Manager $manager, $filename, $types, $chunkSize, OutputInterface $output)
    {
        $params = [
            'search_type' => 'scan',
            'scroll' => '5m',
            'size' => $chunkSize,
            'source' => true,
            'body' => [
                'query' => [
                    'match_all' => [],
                ],
            ],
            'index' => $manager->getIndexName(),
            'type' => $types,
        ];

        $results = new SearchHitIterator(
            new SearchResponseIterator($manager->getClient(), $params)
        );

        $progress = new ProgressBar($output, $results->count());
        $progress->setRedrawFrequency(100);
        $progress->start();

        $metadata = [
            'count' => $results->count(),
            'date' => date(\DateTime::ISO8601),
        ];

        $writer = $this->getWriter($this->getFilePath($filename), $metadata);

        foreach ($results as $data) {
            $doc = array_intersect_key($data, array_flip(['_id', '_type', '_source', 'fields']));
            $writer->push($doc);
            $progress->advance();
        }

        $writer->finalize();
        $progress->finish();
        $output->writeln('');
    }

    /**
     * Returns real file path.
     *
     * @param string $filename
     *
     * @return string
     */
    protected function getFilePath($filename)
    {
        if ($filename{0} == '/' || strstr($filename, ':') !== false) {
            return $filename;
        }

        return getcwd() . '/' . $filename;
    }

    /**
     * Prepares JSON writer.
     *
     * @param string $filename
     * @param array  $metadata
     *
     * @return JsonWriter
     */
    protected function getWriter($filename, $metadata)
    {
        return new JsonWriter($filename, $metadata);
    }
}
