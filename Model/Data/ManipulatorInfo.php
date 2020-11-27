<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterAmqp\Model\Data;

use Websolute\TransporterAmqp\Api\Data\ManipulatorInfoInterface;

class ManipulatorInfo implements ManipulatorInfoInterface
{
    /**
     * @var int
     */
    private $activity_id;

    /**
     * @var string
     */
    private $entity_identifier;

    public function __construct(
        int $activity_id,
        string $entity_identifier
    ) {
        $this->activity_id = $activity_id;
        $this->entity_identifier = $entity_identifier;
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
    public function getEntityIdentifier(): string
    {
        return $this->entity_identifier;
    }
}
