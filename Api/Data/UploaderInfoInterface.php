<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterAmqp\Api\Data;

interface UploaderInfoInterface
{
    /**
     * @return int
     */
    public function getActivityId(): int;

    /**
     * @return string
     */
    public function getUploaderType(): string;
}
