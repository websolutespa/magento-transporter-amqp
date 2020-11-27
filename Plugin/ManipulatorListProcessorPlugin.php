<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterAmqp\Plugin;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Websolute\TransporterActivity\Api\ActivityRepositoryInterface;
use Websolute\TransporterAmqp\Exception\TransporterAmqpException;
use Websolute\TransporterAmqp\Model\ActivityQueueStateInterface;
use Websolute\TransporterAmqp\Model\ActivityStateInterface;
use Websolute\TransporterAmqp\Model\Config;
use Websolute\TransporterAmqp\Model\ManipulatorPublisher;
use Websolute\TransporterBase\Model\ManipulatorListProcessor;
use Websolute\TransporterEntity\Api\Data\EntityInterface;
use Websolute\TransporterEntity\Api\EntityRepositoryInterface;
use Websolute\TransporterManipulation\Api\ManipulationRepositoryInterface;

class ManipulatorListProcessorPlugin
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ManipulatorPublisher
     */
    private $manipulatorPublisher;

    /**
     * @var ActivityRepositoryInterface
     */
    private $activityRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $entityRepository;

    /**
     * @var ManipulationRepositoryInterface
     */
    private $manipulationRepository;

    /**
     * @param Config $config
     * @param ManipulatorPublisher $manipulatorPublisher
     * @param ActivityRepositoryInterface $activityRepository
     * @param EntityRepositoryInterface $entityRepository
     * @param ManipulationRepositoryInterface $manipulationRepository
     */
    public function __construct(
        Config $config,
        ManipulatorPublisher $manipulatorPublisher,
        ActivityRepositoryInterface $activityRepository,
        EntityRepositoryInterface $entityRepository,
        ManipulationRepositoryInterface $manipulationRepository
    ) {
        $this->config = $config;
        $this->manipulatorPublisher = $manipulatorPublisher;
        $this->activityRepository = $activityRepository;
        $this->entityRepository = $entityRepository;
        $this->manipulationRepository = $manipulationRepository;
    }

    /**
     * @param ManipulatorListProcessor $subject
     * @param callable $proceed
     * @param int $activityId
     * @throws TransporterAmqpException|NoSuchEntityException
     */
    public function aroundExecute(ManipulatorListProcessor $subject, callable $proceed, int $activityId)
    {
        if (!$this->config->isEnabledForTranformers()) {
            $proceed($activityId);
            return;
        }

        $activity = $this->activityRepository->getById($activityId);

        try {
            $subject->getLogger()->info(__(
                'activityId:%1 ~ ManipulatorListProcessor ~ Add to queue ~ START',
                $activityId
            ));

            $allActivityEntities = $this->entityRepository->getAllIdentifiersByActivityId($activityId);

            /** @var EntityInterface[] $entities */
            foreach ($allActivityEntities as $entityIdentifier) {
                $this->manipulationRepository->createOrUpdate(
                    $activityId,
                    $entityIdentifier,
                    ActivityQueueStateInterface::ADDED_TO_QUEUE
                );
                $this->manipulatorPublisher->execute($activityId, $entityIdentifier);
            }

            $subject->getLogger()->info(__(
                'activityId:%1 ~ ManipulatorListProcessor ~ Add to queue ~ END',
                $activityId
            ));
        } catch (Exception $e) {
            $activity->setStatus(ActivityStateInterface::MANIPULATE_TO_QUEUE_ERROR);
            $this->activityRepository->save($activity);

            $subject->getLogger()->error(__(
                'activityId:%1 ~ ManipulatorListProcessor ~ Add to queue ~ ERROR ~ error:%3',
                $activityId,
                $e->getMessage()
            ));
            throw new TransporterAmqpException(__($e->getMessage()));
        }

        $activity->setStatus(ActivityStateInterface::MANIPULATE_TO_QUEUE);
        $this->activityRepository->save($activity);
    }
}
