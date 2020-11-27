<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterAmqp\Model\Data;

use Websolute\TransporterAmqp\Api\Data\UploaderInfoInterface;

class UploaderInfo implements UploaderInfoInterface
{
    /**
     * @var int
     */
    private $activity_id;

    /**
     * @var string
     */
    private $uploader_type;

    public function __construct(
        int $activity_id,
        string $uploader_type
    ) {
        $this->activity_id = $activity_id;
        $this->uploader_type = $uploader_type;
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
    public function getUploaderType(): string
    {
        return $this->uploader_type;
    }
}
