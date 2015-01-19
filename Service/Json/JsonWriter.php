<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Service\Json;

/**
 * Serializes records one by one. Outputs given metadata before first record.
 *
 * Sample output:
 * <p>
 * [
 * {"count":2},
 * {"_id":"doc1","title":"Document 1"},
 * {"_id":"doc2","title":"Document 2"}
 * ]
 * </p>
 */
class JsonWriter
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @var resource A file system pointer resource.
     */
    private $handle;

    /**
     * @var array
     */
    private $metadata;

    /**
     * @var int Current record number.
     */
    private $currentPosition = 0;

    /**
     * Constructor.
     *
     * Metadata can contain any fields but only the field "count"
     * is recognized and used while writing to file. If written lines count
     * reaches "count", writer will automatically finalize file.
     *
     * @param string $filename A file in which data will be saved.
     * @param array  $metadata Additional metadata to be stored.
     */
    public function __construct($filename, $metadata = [])
    {
        $this->filename = $filename;
        $this->metadata = $metadata;
    }

    /**
     * Destructor. Closes file handler if open.
     */
    public function __destruct()
    {
        $this->finalize();
    }

    /**
     * Performs initialization.
     */
    protected function initialize()
    {
        if ($this->handle !== null) {
            return;
        }

        $this->handle = fopen($this->filename, 'w');
        fwrite($this->handle, "[\n");
        fwrite($this->handle, json_encode($this->metadata));
    }

    /**
     * Performs finalization.
     */
    public function finalize()
    {
        $this->initialize();

        if (is_resource($this->handle)) {
            fwrite($this->handle, "\n]");
            fclose($this->handle);
        }
    }

    /**
     * Writes single document to stream.
     *
     * @param mixed $document Object to insert into stream.
     *
     * @throws \OverflowException
     */
    public function push($document)
    {
        $this->initialize();
        $this->currentPosition++;

        if (isset($this->metadata['count']) && $this->currentPosition > $this->metadata['count']) {
            throw new \OverflowException(
                sprintf('This writer was set up to write %d documents, got more.', $this->metadata['count'])
            );
        }

        fwrite($this->handle, ",\n");
        fwrite($this->handle, json_encode($document));

        if (isset($this->metadata['count']) && $this->currentPosition == $this->metadata['count']) {
            $this->finalize();
        }
    }
}
