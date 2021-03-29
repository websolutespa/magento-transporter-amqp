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
use Websolute\TransporterManipulation\Api\ManipulationRepositoryInterface;

class ActivityCheckIsTimeToMakeAsManipulateDequeued
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
     * @var ManipulationRepositoryInterface
     */
    private $manipulationRepository;

    /**
     * @param ActivityRepositoryInterface $activityRepository
     * @param TransporterListInterface $transporterList
     * @param ManipulationRepositoryInterface $manipulationRepository
     */
    public function __construct(
        ActivityRepositoryInterface $activityRepository,
        TransporterListInterface $transporterList,
        ManipulationRepositoryInterface $manipulationRepository
    ) {
        $this->activityRepository = $activityRepository;
        $this->transporterList = $transporterList;
        $this->manipulationRepository = $manipulationRepository;
    }

    /**
     * @param int $activityId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function execute(int $activityId): bool
    {
        $manipulations = $this->manipulationRepository->getAllByActivityId($activityId);

        if (!count($manipulations)) {
            return false;
        }

        foreach ($manipulations as $manipulation) {
            if ($manipulation->getStatus() !== ActivityQueueStateInterface::DEQUEUED) {
                return false;
            }
        }

        $activity = $this->activityRepository->getById($activityId);
        $activity->setStatus(\Websolute\TransporterActivity\Model\ActivityStateInterface::MANIPULATED);
        $this->activityRepository->save($activity);

        return true;
    }
}
