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

use ONGR\ElasticsearchBundle\DSL\Query\MatchAllQuery;
use ONGR\ElasticsearchBundle\ORM\Manager;
use ONGR\ElasticsearchBundle\ORM\Repository;
use ONGR\ElasticsearchBundle\Result\RawResultIterator;
use ONGR\ElasticsearchBundle\Service\Json\JsonWriter;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ExportService class.
 */
class ExportService
{
    const SCROLL_DURATION = '5m';

    /**
     * Exports es index to provided file.
     *
     * @param Manager         $manager
     * @param string          $filename
     * @param int             $chunkSize
     * @param OutputInterface $output
     */
    public function exportIndex($manager, $filename, $chunkSize, OutputInterface $output)
    {
        $types = $manager->getTypesMapping();
        $repo = $manager->getRepository($types);

        $results = $this->getResults($repo, $chunkSize);

        if (class_exists('\Symfony\Component\Console\Helper\ProgressBar')) {
            $progress = new ProgressBar($output, $results->getTotalCount());
            $progress->setRedrawFrequency(100);
            $progress->start();
        } else {
            $progress = new ProgressHelper();
            $progress->setRedrawFrequency(100);
            $progress->start($output, $results->getTotalCount());
        }

        $metadata = [
            'count' => $results->getTotalCount(),
            'date' => date(\DateTime::ISO8601),
        ];

        $writer = $this->getWriter($this->getFilePath($filename), $metadata);

        foreach ($results as $data) {
            $writer->push(array_intersect_key($data, array_flip(['_id', '_type', '_source'])));
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
     * Returns scan results iterator.
     *
     * @param Repository $repository
     * @param int        $chunkSize
     *
     * @return RawResultIterator
     */
    protected function getResults($repository, $chunkSize)
    {
        $search = $repository->createSearch();
        $search->setScroll(self::SCROLL_DURATION)
            ->setSize($chunkSize);
        $search->addQuery(new MatchAllQuery());

        return $repository->execute($search, Repository::RESULTS_RAW_ITERATOR);
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
