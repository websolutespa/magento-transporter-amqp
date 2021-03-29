<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterAmqp\Model;

interface ActivityQueueStateInterface
{
    const ADDED_TO_QUEUE = 'added_to_queue';
    const IN_DEQUEUE = 'in_dequeue';
    const DEQUEUED = 'dequeued';
    const DEQUEUED_ERROR = 'dequeue_error';
}
