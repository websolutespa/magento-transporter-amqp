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
use Websolute\TransporterAmqp\Model\DownloaderPublisher;
use Websolute\TransporterAmqp\Api\DownloaderWaitMeBeforeContinueInterface;
use Websolute\TransporterBase\Model\DownloaderListProcessor;
use Websolute\TransporterDownload\Api\DownloadRepositoryInterface;

class DownloaderListProcessorPlugin
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var DownloaderPublisher
     */
    private $downloadPublisher;

    /**
     * @var ActivityRepositoryInterface
     */
    private $activityRepository;

    /**
     * @var DownloadRepositoryInterface
     */
    private $downloadRepository;

    /**
     * @param Config $config
     * @param DownloaderPublisher $downloadPublisher
     * @param ActivityRepositoryInterface $activityRepository
     * @param DownloadRepositoryInterface $downloadRepository
     */
    public function __construct(
        Config $config,
        DownloaderPublisher $downloadPublisher,
        ActivityRepositoryInterface $activityRepository,
        DownloadRepositoryInterface $downloadRepository
    ) {
        $this->config = $config;
        $this->downloadPublisher = $downloadPublisher;
        $this->activityRepository = $activityRepository;
        $this->downloadRepository = $downloadRepository;
    }

    /**
     * @param DownloaderListProcessor $subject
     * @param callable $proceed
     * @param int $activityId
     * @throws TransporterAmqpException|NoSuchEntityException
     */
    public function aroundExecute(DownloaderListProcessor $subject, callable $proceed, int $activityId)
    {
        if (!$this->config->isEnabledForDownloaders()) {
            $proceed($activityId);
            return;
        }

        $activity = $this->activityRepository->getById($activityId);

        try {
            $subject->getLogger()->info(__(
                'activityId:%1 ~ DownloaderListProcessor ~ Add to queue ~ type:%2 ~ START',
                $activityId,
                $activity->getType()
            ));
            $extra = $activity->getExtra();
            foreach ($subject->getDownloaders() as $downloaderType => $downloader) {
                if ($extra->getData('downloader_' . $downloaderType) === 'published to queue') {
                    continue;
                }
                $this->downloadRepository->createOrUpdate(
                    $activityId,
                    $downloaderType,
                    ActivityQueueStateInterface::ADDED_TO_QUEUE
                );
                $this->downloadPublisher->execute($activityId, $downloaderType);
                $activity->addExtraArray(['downloader_' . $downloaderType => 'published to queue']);
                if ($downloader instanceof DownloaderWaitMeBeforeContinueInterface) {
                    break;
                }
            }
            $subject->getLogger()->info(__(
                'activityId:%1 ~ DownloaderListProcessor ~ Add to queue ~ type:%2 ~ END',
                $activityId,
                $activity->getType()
            ));
        } catch (Exception $e) {
            $activity->setStatus(ActivityStateInterface::DOWNLOAD_TO_QUEUE_ERROR);
            $this->activityRepository->save($activity);

            $subject->getLogger()->error(__(
                'activityId:%1 ~ DownloaderListProcessor ~ Add to queue ~ type:%2 ~ ERROR ~ error:%3',
                $activityId,
                $activity->getType(),
                $e->getMessage()
            ));
            throw new TransporterAmqpException(__($e->getMessage()));
        }

        $activity->setStatus(ActivityStateInterface::DOWNLOAD_TO_QUEUE);
        $this->activityRepository->save($activity);
    }
}
