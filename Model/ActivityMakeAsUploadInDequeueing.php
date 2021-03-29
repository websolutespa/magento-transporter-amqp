<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterAmqp\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Websolute\TransporterActivity\Api\ActivityRepositoryInterface;
use Websolute\TransporterBase\Api\TransporterListInterface;

class ActivityMakeAsUploadInDequeueing
{
    /**
     * @var ActivityRepositoryInterface
     */
    private $activityRepository;

    /**
     * @var TransporterListInterface
     */
    private $transporterList;

    /**
     * @param ActivityRepositoryInterface $activityRepository
     * @param TransporterListInterface $transporterList
     */
    public function __construct(
        ActivityRepositoryInterface $activityRepository,
        TransporterListInterface $transporterList
    ) {
        $this->activityRepository = $activityRepository;
        $this->transporterList = $transporterList;
    }

    /**
     * @param int $activityId
     * @throws NoSuchEntityException
     */
    public function execute(int $activityId)
    {
        $activity = $this->activityRepository->getById($activityId);
        if ($activity->getStatus() !== ActivityStateInterface::UPLOAD_IN_DEQUEUING) {
            $activity->setStatus(ActivityStateInterface::UPLOAD_IN_DEQUEUING);
            $this->activityRepository->save($activity);
        }
    }
}
