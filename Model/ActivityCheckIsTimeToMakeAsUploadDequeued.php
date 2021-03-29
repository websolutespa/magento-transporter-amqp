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
use Websolute\TransporterUpload\Api\UploadRepositoryInterface;

class ActivityCheckIsTimeToMakeAsUploadDequeued
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
     * @var UploadRepositoryInterface
     */
    private $uploadRepository;

    /**
     * @param ActivityRepositoryInterface $activityRepository
     * @param TransporterListInterface $transporterList
     * @param UploadRepositoryInterface $uploadRepository
     */
    public function __construct(
        ActivityRepositoryInterface $activityRepository,
        TransporterListInterface $transporterList,
        UploadRepositoryInterface $uploadRepository
    ) {
        $this->activityRepository = $activityRepository;
        $this->transporterList = $transporterList;
        $this->uploadRepository = $uploadRepository;
    }

    /**
     * @param int $activityId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function execute(int $activityId): bool
    {
        $uploads = $this->uploadRepository->getAllByActivityId($activityId);

        if (!count($uploads)) {
            return false;
        }

        foreach ($uploads as $upload) {
            if ($upload->getStatus() !== ActivityQueueStateInterface::DEQUEUED) {
                return false;
            }
        }

        $activity = $this->activityRepository->getById($activityId);
        $activity->setStatus(\Websolute\TransporterActivity\Model\ActivityStateInterface::UPLOADED);
        $this->activityRepository->save($activity);

        return true;
    }
}
