<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterAmqp\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Websolute\TransporterActivity\Api\ActivityRepositoryInterface;
use Websolute\TransporterAmqp\Api\Data\ManipulatorInfoInterface;
use Websolute\TransporterBase\Api\TransporterListInterface;
use Websolute\TransporterBase\Exception\TransporterException;
use Websolute\TransporterEntity\Api\EntityRepositoryInterface;
use Websolute\TransporterManipulation\Api\ManipulationRepositoryInterface;

class ManipulatorConsumer
{
    /**
     * @var TransporterListInterface
     */
    private $transporterList;

    /**
     * @var ActivityRepositoryInterface
     */
    private $activityRepository;

    /**
     * @var ActivityCheckIsTimeToMakeAsManipulateDequeued
     */
    private $activityCheckIsTimeToMakeAsManipulateDequeued;

    /**
     * @var ActivityMakeAsManipulateInDequeueing
     */
    private $activityMakeAsManipulateInDequeueing;

    /**
     * @var EntityRepositoryInterface
     */
    private $entityRepository;

    /**
     * @var ManipulationRepositoryInterface
     */
    private $manipulationRepository;

    /**
     * @param TransporterListInterface $transporterList
     * @param ActivityRepositoryInterface $activityRepository
     * @param ActivityCheckIsTimeToMakeAsManipulateDequeued $activityCheckIsTimeToMakeAsManipulateDequeued
     * @param ActivityMakeAsManipulateInDequeueing $activityMakeAsManipulateInDequeueing
     * @param EntityRepositoryInterface $entityRepository
     * @param ManipulationRepositoryInterface $manipulationRepository
     */
    public function __construct(
        TransporterListInterface $transporterList,
        ActivityRepositoryInterface $activityRepository,
        ActivityCheckIsTimeToMakeAsManipulateDequeued $activityCheckIsTimeToMakeAsManipulateDequeued,
        ActivityMakeAsManipulateInDequeueing $activityMakeAsManipulateInDequeueing,
        EntityRepositoryInterface $entityRepository,
        ManipulationRepositoryInterface $manipulationRepository
    ) {
        $this->transporterList = $transporterList;
        $this->activityRepository = $activityRepository;
        $this->activityCheckIsTimeToMakeAsManipulateDequeued = $activityCheckIsTimeToMakeAsManipulateDequeued;
        $this->activityMakeAsManipulateInDequeueing = $activityMakeAsManipulateInDequeueing;
        $this->entityRepository = $entityRepository;
        $this->manipulationRepository = $manipulationRepository;
    }

    /**
     * @param ManipulatorInfoInterface $manipulatorInfo
     * @throws NoSuchEntityException|TransporterException
     */
    public function process(ManipulatorInfoInterface $manipulatorInfo)
    {
        $activityId = $manipulatorInfo->getActivityId();
        $entityIdentifier = $manipulatorInfo->getEntityIdentifier();
        $activity = $this->activityRepository->getById($activityId);
        $activityType = $activity->getType();

        try {
            $manipulatorList = $this->transporterList->getManipulatorList($activityType);
            $manipulators = $manipulatorList->getManipulators();

            $this->activityMakeAsManipulateInDequeueing->execute($activityId);

            $this->manipulationRepository->update(
                $activityId,
                $entityIdentifier,
                ActivityQueueStateInterface::IN_DEQUEUE
            );

            $entities = $this->entityRepository->getAllByActivityIdAndIdentifierGroupedByIdentifier(
                $activityId,
                $entityIdentifier
            );

            foreach ($manipulators as $manipulatorType => $manipulator) {
                $manipulator->execute($activityId, $manipulatorType, $entityIdentifier, $entities);
            }

            foreach ($entities as $entity) {
                $this->entityRepository->save($entity);
            }

            $this->manipulationRepository->update(
                $activityId,
                $entityIdentifier,
                ActivityQueueStateInterface::DEQUEUED
            );

            $this->activityCheckIsTimeToMakeAsManipulateDequeued->execute($activityId);
        } catch (TransporterException $e) {
            $activity->setStatus(\Websolute\TransporterActivity\Model\ActivityStateInterface::MANIPULATE_ERROR);
            if (isset($manipulatorType)) {
                $activity->addExtraArray(
                    [
                        'manipulator_' . $manipulatorType => $entityIdentifier,
                        'error' => $e->getMessage()
                    ]
                );
            }
            $this->activityRepository->save($activity);

            $this->manipulationRepository->update(
                $activityId,
                $entityIdentifier,
                ActivityQueueStateInterface::DEQUEUED_ERROR
            );
            throw $e;
        }
    }
}
