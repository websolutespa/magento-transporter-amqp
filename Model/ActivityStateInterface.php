<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterAmqp\Model;

interface ActivityStateInterface
{
    const DOWNLOAD_TO_QUEUE = 'download_added_to_queue';
    const DOWNLOAD_TO_QUEUE_ERROR = 'download_added_to_queue_error';
    const DOWNLOAD_IN_DEQUEUING = 'download_in_dequeuing';

    const MANIPULATE_TO_QUEUE = 'manipulate_added_to_queue';
    const MANIPULATE_TO_QUEUE_ERROR = 'manipulate_added_to_queue_error';
    const MANIPULATE_IN_DEQUEUING = 'manipulate_in_dequeuing';

    const UPLOAD_TO_QUEUE = 'upload_added_to_queue';
    const UPLOAD_TO_QUEUE_ERROR = 'upload_added_to_queue_error';
    const UPLOAD_IN_DEQUEUING = 'upload_in_dequeuing';
}
