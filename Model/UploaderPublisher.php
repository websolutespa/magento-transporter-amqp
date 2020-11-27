<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterAmqp\Model;

use Magento\Framework\MessageQueue\PublisherInterface;
use Websolute\TransporterAmqp\Model\Data\UploaderInfoFactory;

class UploaderPublisher
{
    const TOPIC_NAME = 'transporter.uploader';

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var UploaderInfoFactory
     */
    private $uploaderInfoFactory;

    /**
     * @param PublisherInterface $publisher
     * @param UploaderInfoFactory $uploaderInfoFactory
     */
    public function __construct(
        PublisherInterface $publisher,
        UploaderInfoFactory $uploaderInfoFactory
    ) {
        $this->publisher = $publisher;
        $this->uploaderInfoFactory = $uploaderInfoFactory;
    }

    /**
     * @param int $activityId
     * @param string $uploaderType
     */
    public function execute(int $activityId, string $uploaderType): void
    {
        $uploaderInfo = $this->uploaderInfoFactory->create(
            [
                'activity_id' => $activityId,
                'uploader_type' => $uploaderType
            ]
        );
        $this->publisher->publish(self::TOPIC_NAME, $uploaderInfo);
    }
}
