<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterAmqp\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Websolute\TransporterActivity\Api\ActivityRepositoryInterface;
use Websolute\TransporterBase\Api\TransporterListInterface;
use Websolute\TransporterDownload\Api\DownloadRepositoryInterface;

class ActivityCheckIsTimeToMakeAsDownloadDequeued
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
     * @var DownloadRepositoryInterface
     */
    private $downloadRepository;

    /**
     * @param ActivityRepositoryInterface $activityRepository
     * @param TransporterListInterface $transporterList
     * @param DownloadRepositoryInterface $downloadRepository
     */
    public function __construct(
        ActivityRepositoryInterface $activityRepository,
        TransporterListInterface $transporterList,
        DownloadRepositoryInterface $downloadRepository
    ) {
        $this->activityRepository = $activityRepository;
        $this->transporterList = $transporterList;
        $this->downloadRepository = $downloadRepository;
    }

    /**
     * @param int $activityId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function execute(int $activityId): bool
    {
        $downloads = $this->downloadRepository->getAllByActivityId($activityId);

        if (!count($downloads)) {
            return false;
        }

        foreach ($downloads as $download) {
            if ($download->getStatus() !== ActivityQueueStateInterface::DEQUEUED) {
                return false;
            }
        }

        $activity = $this->activityRepository->getById($activityId);
        $activity->setStatus(\Websolute\TransporterActivity\Model\ActivityStateInterface::DOWNLOADED);
        $this->activityRepository->save($activity);

        return true;
    }
}
