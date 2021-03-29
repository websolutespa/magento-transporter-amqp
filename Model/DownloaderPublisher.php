<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterAmqp\Model;

use Magento\Framework\MessageQueue\PublisherInterface;
use Websolute\TransporterAmqp\Model\Data\DownloaderInfoFactory;

class DownloaderPublisher
{
    const TOPIC_NAME = 'transporter.downloader';

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var DownloaderInfoFactory
     */
    private $downloaderInfoFactory;

    /**
     * @param PublisherInterface $publisher
     * @param DownloaderInfoFactory $downloaderInfoFactory
     */
    public function __construct(
        PublisherInterface $publisher,
        DownloaderInfoFactory $downloaderInfoFactory
    ) {
        $this->publisher = $publisher;
        $this->downloaderInfoFactory = $downloaderInfoFactory;
    }

    /**
     * @param int $activityId
     * @param string $downloaderType
     */
    public function execute(int $activityId, string $downloaderType): void
    {
        $downloaderInfo = $this->downloaderInfoFactory->create(
            [
                'activity_id' => $activityId,
                'downloader_type' => $downloaderType
            ]
        );
        $this->publisher->publish(self::TOPIC_NAME, $downloaderInfo);
    }
}
