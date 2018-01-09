<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\app\fixture\TestBundle\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

trait LongDescriptionTrait
{
    /**
     * @ES\Property(type="text", name="long_description")
     * @var string
     */
    private $long_description;

    /**
     * @param string $long_description
     */
    public function setLongDescription($long_description)
    {
        $this->long_description = $long_description;
        return $this;
    }

    /**
     * @return string
     */
    public function getLongDescription()
    {
        return $this->long_description;
    }
}
