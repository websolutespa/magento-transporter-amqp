<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterAmqp\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Websolute\TransporterActivity\Api\ActivityRepositoryInterface;
use Websolute\TransporterAmqp\Api\Data\UploaderInfoInterface;
use Websolute\TransporterBase\Api\TransporterListInterface;
use Websolute\TransporterBase\Exception\TransporterException;
use Websolute\TransporterUpload\Api\UploadRepositoryInterface;

class UploaderConsumer
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
     * @var ActivityCheckIsTimeToMakeAsUploadDequeued
     */
    private $activityCheckIsTimeToMakeAsUploadDequeued;

    /**
     * @var ActivityMakeAsUploadInDequeueing
     */
    private $activityMakeAsUploadInDequeueing;
    /**
     * @var UploadRepositoryInterface
     */
    private $uploadRepository;

    /**
     * @param TransporterListInterface $transporterList
     * @param ActivityRepositoryInterface $activityRepository
     * @param ActivityCheckIsTimeToMakeAsUploadDequeued $activityCheckIsTimeToMakeAsUploadDequeued
     * @param ActivityMakeAsUploadInDequeueing $activityMakeAsUploadInDequeueing
     * @param UploadRepositoryInterface $uploadRepository
     */
    public function __construct(
        TransporterListInterface $transporterList,
        ActivityRepositoryInterface $activityRepository,
        ActivityCheckIsTimeToMakeAsUploadDequeued $activityCheckIsTimeToMakeAsUploadDequeued,
        ActivityMakeAsUploadInDequeueing $activityMakeAsUploadInDequeueing,
        UploadRepositoryInterface $uploadRepository
    ) {
        $this->transporterList = $transporterList;
        $this->activityRepository = $activityRepository;
        $this->activityCheckIsTimeToMakeAsUploadDequeued = $activityCheckIsTimeToMakeAsUploadDequeued;
        $this->activityMakeAsUploadInDequeueing = $activityMakeAsUploadInDequeueing;
        $this->uploadRepository = $uploadRepository;
    }

    /**
     * @param UploaderInfoInterface $uploaderInfo
     * @throws NoSuchEntityException|TransporterException
     */
    public function process(UploaderInfoInterface $uploaderInfo)
    {
        $activityId = $uploaderInfo->getActivityId();
        $uploaderType = $uploaderInfo->getUploaderType();
        $activity = $this->activityRepository->getById($activityId);
        $activityType = $activity->getType();

        try {
            $this->activityMakeAsUploadInDequeueing->execute($activityId);

            $uploaderList = $this->transporterList->getUploaderList($activityType);
            $uploaders = $uploaderList->getUploaders();

            if (!array_key_exists($uploaderType, $uploaders)) {
                throw new NoSuchEntityException(__(
                    'Unable to find Uploader with type %1',
                    $activityType
                ));
            }
            $uploader = $uploaders[$uploaderType];

            $uploader->execute($activityId, $uploaderType);

            $this->uploadRepository->update(
                $activityId,
                $uploaderType,
                ActivityQueueStateInterface::DEQUEUED
            );

            $this->activityCheckIsTimeToMakeAsUploadDequeued->execute($activityId);
        } catch (TransporterException $e) {
            $activity->setStatus(\Websolute\TransporterActivity\Model\ActivityStateInterface::UPLOAD_ERROR);
            $activity->addExtraArray(
                [
                    'uploader_' . $uploaderType => 'error',
                    'error' => $e->getMessage()
                ]
            );
            $this->activityRepository->save($activity);

            $this->uploadRepository->update(
                $activityId,
                $uploaderType,
                ActivityQueueStateInterface::DEQUEUED_ERROR
            );
            throw $e;
        }
    }
}
