<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterAmqp\Model\Data;

use Websolute\TransporterAmqp\Api\Data\DownloaderInfoInterface;

class DownloaderInfo implements DownloaderInfoInterface
{
    /**
     * @var int
     */
    private $activity_id;

    /**
     * @var string
     */
    private $downloader_type;

    public function __construct(
        int $activity_id,
        string $downloader_type
    ) {
        $this->activity_id = $activity_id;
        $this->downloader_type = $downloader_type;
    }

    /**
     * @return int
     */
    public function getActivityId(): int
    {
        return $this->activity_id;
    }

    /**
     * @return string
     */
    public function getDownloaderType(): string
    {
        return $this->downloader_type;
    }
}
