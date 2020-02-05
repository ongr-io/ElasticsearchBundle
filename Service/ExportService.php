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
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ExportService class.
 */
class ExportService
{
    public function exportIndex(
        IndexService $index,
        $filename,
        $chunkSize,
        OutputInterface $output,
        $maxLinesInFile = 300000
    ) {
        $search = new Search();
        $search->addQuery(new MatchAllQuery());
        $search->setSize($chunkSize);
        $search->setScroll('2m');

        $searchResults = $index->search($search->toArray(), $search->getUriParams());

        $results = new RawIterator(
            $searchResults,
            $index,
            null,
            [
                'duration' => '2m',
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
        $writer = $this->getWriter($this->getFilePath($filename.'.json'), $metadata['count']);

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
                $writer = $this->getWriter($this->getFilePath($filename."_".$fileCounter.".json"), $metadata['count']);
                $counter = 0;
            }

            $doc = array_intersect_key($data, array_flip(['_id', '_source']));
            $writer->push($doc);
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
    protected function getFilePath($filename): string
    {
        if ($filename[0] == '/' || strstr($filename, ':') !== false) {
            return $filename;
        }

        return getcwd() . '/' . $filename;
    }

    protected function getWriter(string $filename, int $count): JsonWriter
    {
        return new JsonWriter($filename, $count);
    }

    /**
     * @param int $resultsCount
     * @param int $maxLinesInFile
     * @param int $fileCounter
     *
     * @return int
     */
    protected function getFileCount($resultsCount, $maxLinesInFile, $fileCounter): int
    {
        $leftToInsert = $resultsCount - ($fileCounter * $maxLinesInFile);
        if ($leftToInsert <= $maxLinesInFile) {
            $count = $leftToInsert;
        } else {
            $count = $maxLinesInFile;
        }

        return (int) $count;
    }
}
