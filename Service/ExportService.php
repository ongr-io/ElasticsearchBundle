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
use ONGR\ElasticsearchBundle\Result\RawIterator;
use ONGR\ElasticsearchBundle\Service\Json\JsonWriter;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
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
     * @param int             $maxLinesInFile
     * @param OutputInterface $output
     */
    public function exportIndex(
        Manager $manager,
        $filename,
        $types,
        $chunkSize,
        OutputInterface $output,
        $maxLinesInFile = 300000
    ) {

        $search = new Search();
        $search->addQuery(new MatchAllQuery());
        $search->setSize($chunkSize);
        $search->addSort(new FieldSort('_doc'));

        $queryParameters = [
                '_source' => true,
                'scroll' => '10m',
            ];

        $searchResults = $manager->search($types, $search->toArray(), $queryParameters);

        $results = new RawIterator(
            $searchResults,
            $manager,
            [
                'duration' => $queryParameters['scroll'],
                '_scroll_id' => $searchResults['_scroll_id'],
            ]
        );

        $progress = new ProgressBar($output, $results->count());
        $progress->setRedrawFrequency(100);
        $progress->start();

        $counter = $fileCounter = 0;
        $count = $this->getFileCount($results->count(), $maxLinesInFile, $fileCounter);

        $date = date(\DateTime::ISO8601);
        $metadata = [
            'count' => $count,
            'date' => $date,
        ];

        $filename = str_replace('.json', '', $filename);
        $writer = $this->getWriter($this->getFilePath($filename.'.json'), $metadata);

        $file = [];
        foreach ($results as $data) {
            if ($counter >= $maxLinesInFile) {
                $writer->finalize();
                $writer = null;
                $fileCounter++;
                $count = $this->getFileCount($results->count(), $maxLinesInFile, $fileCounter);
                $metadata = [
                    'count' => $count,
                    'date' => $date,
                ];
                $writer = $this->getWriter($this->getFilePath($filename."_".$fileCounter.".json"), $metadata);
                $counter = 0;
            }

            $doc = array_intersect_key($data, array_flip(['_id', '_type', '_source']));
            $writer->push($doc);
            $file[] = $doc;
            $progress->advance();
            $counter++;
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

    /**
     * @param int $resultsCount
     * @param int $maxLinesInFile
     * @param int $fileCounter
     *
     * @return int
     */
    protected function getFileCount($resultsCount, $maxLinesInFile, $fileCounter)
    {
        $leftToInsert = $resultsCount - ($fileCounter * $maxLinesInFile);
        if ($leftToInsert <= $maxLinesInFile) {
            $count = $leftToInsert;
        } else {
            $count = $maxLinesInFile;
        }

        return $count;
    }
}
