<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterAmqp\Model;

use Magento\Framework\MessageQueue\PublisherInterface;
use Websolute\TransporterAmqp\Model\Data\ManipulatorInfoFactory;

class ManipulatorPublisher
{
    const TOPIC_NAME = 'transporter.manipulator';

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var ManipulatorInfoFactory
     */
    private $manipulatorInfoFactory;

    /**
     * @param PublisherInterface $publisher
     * @param ManipulatorInfoFactory $manipulatorInfoFactory
     */
    public function __construct(
        PublisherInterface $publisher,
        ManipulatorInfoFactory $manipulatorInfoFactory
    ) {
        $this->publisher = $publisher;
        $this->manipulatorInfoFactory = $manipulatorInfoFactory;
    }

    /**
     * @param int $activityId
     * @param string $entityIdentifier
     */
    public function execute(int $activityId, string $entityIdentifier): void
    {
        $manipulatorInfo = $this->manipulatorInfoFactory->create(
            [
                'activity_id' => $activityId,
                'entity_identifier' => $entityIdentifier
            ]
        );
        $this->publisher->publish(self::TOPIC_NAME, $manipulatorInfo);
    }
}
