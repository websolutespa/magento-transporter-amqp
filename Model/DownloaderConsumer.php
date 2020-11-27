<?php
/*
 * Copyright © Websolute spa. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Websolute\TransporterAmqp\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Websolute\TransporterActivity\Api\ActivityRepositoryInterface;
use Websolute\TransporterAmqp\Api\Data\DownloaderInfoInterface;
use Websolute\TransporterBase\Api\TransporterListInterface;
use Websolute\TransporterBase\Exception\TransporterException;
use Websolute\TransporterDownload\Api\DownloadRepositoryInterface;

class DownloaderConsumer
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
     * @var ActivityCheckIsTimeToMakeAsDownloadDequeued
     */
    private $activityCheckIsTimeToMakeAsDownloadDequeued;

    /**
     * @var ActivityMakeAsDownloadInDequeueing
     */
    private $activityMakeAsDownloadInDequeueing;
    /**
     * @var DownloadRepositoryInterface
     */
    private $downloadRepository;

    /**
     * @param TransporterListInterface $transporterList
     * @param ActivityRepositoryInterface $activityRepository
     * @param ActivityCheckIsTimeToMakeAsDownloadDequeued $activityCheckIsTimeToMakeAsDownloadDequeued
     * @param ActivityMakeAsDownloadInDequeueing $activityMakeAsDownloadInDequeueing
     * @param DownloadRepositoryInterface $downloadRepository
     */
    public function __construct(
        TransporterListInterface $transporterList,
        ActivityRepositoryInterface $activityRepository,
        ActivityCheckIsTimeToMakeAsDownloadDequeued $activityCheckIsTimeToMakeAsDownloadDequeued,
        ActivityMakeAsDownloadInDequeueing $activityMakeAsDownloadInDequeueing,
        DownloadRepositoryInterface $downloadRepository
    ) {
        $this->transporterList = $transporterList;
        $this->activityRepository = $activityRepository;
        $this->activityCheckIsTimeToMakeAsDownloadDequeued = $activityCheckIsTimeToMakeAsDownloadDequeued;
        $this->activityMakeAsDownloadInDequeueing = $activityMakeAsDownloadInDequeueing;
        $this->downloadRepository = $downloadRepository;
    }

    /**
     * @param DownloaderInfoInterface $downloaderInfo
     * @throws NoSuchEntityException|TransporterException
     */
    public function process(DownloaderInfoInterface $downloaderInfo)
    {
        $activityId = $downloaderInfo->getActivityId();
        $downloaderType = $downloaderInfo->getDownloaderType();

        $activity = $this->activityRepository->getById($activityId);
        $activityType = $activity->getType();

        try {

            $this->activityMakeAsDownloadInDequeueing->execute($activityId);

            $downloaderList = $this->transporterList->getDownloaderList($activityType);
            $downloaders = $downloaderList->getDownloaders();

            if (!array_key_exists($downloaderType, $downloaders)) {
                throw new NoSuchEntityException(__(
                    'Unable to find Downloader with type %1',
                    $activityType
                ));
            }
            $downloader = $downloaders[$downloaderType];

            $downloader->execute($activityId, $downloaderType);

            $this->downloadRepository->update(
                $activityId,
                $downloaderType,
                ActivityQueueStateInterface::DEQUEUED
            );

            $downloaderList->execute($activityId);

            $this->activityCheckIsTimeToMakeAsDownloadDequeued->execute($activityId);
        } catch (TransporterException $e) {
            $activity->setStatus(\Websolute\TransporterActivity\Model\ActivityStateInterface::DOWNLOAD_ERROR);
            $activity->addExtraArray(
                [
                    'downloader_' . $downloaderType => 'error',
                    'error' => $e->getMessage()
                ]
            );
            $this->activityRepository->save($activity);

            $this->downloadRepository->update(
                $activityId,
                $downloaderType,
                ActivityQueueStateInterface::DEQUEUED_ERROR
            );
            throw $e;
        }
    }
}
