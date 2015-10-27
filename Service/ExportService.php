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

use ONGR\ElasticsearchBundle\Result\RawIterator;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
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
        $typesMapping = $manager->getMetadataCollector()->getMappings($manager->getConfig()['mappings']);
        $typesToExport = [];
        if ($types) {
            foreach ($types as $type) {
                if (!array_key_exists($type, $typesMapping)) {
                    throw new \InvalidArgumentException(sprintf('Type "%s" does not exist.', $type));
                }

                $typesToExport[] = $typesMapping[$type]['bundle'].':'.$typesMapping[$type]['class'];
            }
        } else {
            foreach ($typesMapping as $type => $typeConfig) {
                $typesToExport[] = $typeConfig['bundle'].':'.$typeConfig['class'];
            }
        }

        $repo = $manager->getRepository($typesToExport);

        $results = $this->getResults($repo, $chunkSize);

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
     * Returns scan results iterator.
     *
     * @param Repository $repository
     * @param int        $chunkSize
     *
     * @return RawIterator
     */
    protected function getResults(Repository $repository, $chunkSize)
    {
        $search = $repository->createSearch();
        $search
            ->setScroll()
            ->setSize($chunkSize);
        $search->addQuery(new MatchAllQuery());
        $search->setFields(['_parent']);
        $search->setSource(true);

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
