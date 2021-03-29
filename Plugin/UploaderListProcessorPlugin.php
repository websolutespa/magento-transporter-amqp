<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
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
use Websolute\TransporterAmqp\Model\UploaderPublisher;
use Websolute\TransporterBase\Model\UploaderListProcessor;
use Websolute\TransporterUpload\Api\UploadRepositoryInterface;

class UploaderListProcessorPlugin
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var UploaderPublisher
     */
    private $uploadPublisher;

    /**
     * @var ActivityRepositoryInterface
     */
    private $activityRepository;

    /**
     * @var UploadRepositoryInterface
     */
    private $uploadRepository;

    /**
     * @param Config $config
     * @param UploaderPublisher $uploadPublisher
     * @param ActivityRepositoryInterface $activityRepository
     * @param UploadRepositoryInterface $uploadRepository
     */
    public function __construct(
        Config $config,
        UploaderPublisher $uploadPublisher,
        ActivityRepositoryInterface $activityRepository,
        UploadRepositoryInterface $uploadRepository
    ) {
        $this->config = $config;
        $this->uploadPublisher = $uploadPublisher;
        $this->activityRepository = $activityRepository;
        $this->uploadRepository = $uploadRepository;
    }

    /**
     * @param UploaderListProcessor $subject
     * @param callable $proceed
     * @param int $activityId
     * @throws TransporterAmqpException|NoSuchEntityException
     */
    public function aroundExecute(UploaderListProcessor $subject, callable $proceed, int $activityId)
    {
        if (!$this->config->isEnabledForUploaders()) {
            $proceed($activityId);
            return;
        }

        $activity = $this->activityRepository->getById($activityId);

        try {
            $subject->getLogger()->info(__(
                'activityId:%1 ~ UploaderListProcessor ~ Add to queue ~ type:%2 ~ START',
                $activityId,
                $activity->getType()
            ));
            foreach ($subject->getUploaders() as $uploaderType => $uploader) {
                $this->uploadRepository->createOrUpdate(
                    $activityId,
                    $uploaderType,
                    ActivityQueueStateInterface::ADDED_TO_QUEUE
                );
                $this->uploadPublisher->execute($activityId, $uploaderType);
            }
            $subject->getLogger()->info(__(
                'activityId:%1 ~ UploaderListProcessor ~ Add to queue ~ type:%2 ~ END',
                $activityId,
                $activity->getType()
            ));
        } catch (Exception $e) {
            $activity->setStatus(ActivityStateInterface::UPLOAD_TO_QUEUE_ERROR);
            $this->activityRepository->save($activity);

            $subject->getLogger()->error(__(
                'activityId:%1 ~ UploaderListProcessor ~ Add to queue ~ type:%2 ~ ERROR ~ error:%3',
                $activityId,
                $activity->getType(),
                $e->getMessage()
            ));
            throw new TransporterAmqpException(__($e->getMessage()));
        }

        $activity->setStatus(ActivityStateInterface::UPLOAD_TO_QUEUE);
        $this->activityRepository->save($activity);
    }
}
