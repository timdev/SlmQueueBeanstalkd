<?php

namespace SlmQueueBeanstalkd\Worker;

use Exception;
use Pheanstalk_Pheanstalk as Pheanstalk;
use SlmQueue\Job\JobInterface;
use SlmQueue\Queue\QueueInterface;
use SlmQueue\Worker\AbstractWorker;
use SlmQueueBeanstalkd\Job\Exception as JobException;
use SlmQueueBeanstalkd\Queue\TubeInterface;
use Zend\EventManager\EventManagerAwareInterface;

/**
 * Worker for Beanstalkd
 */
class Worker extends AbstractWorker
{
    /**
     * {@inheritDoc}
     */
    public function processJob(JobInterface $job, QueueInterface $queue)
    {
        if (!$queue instanceof TubeInterface) {
            return;
        }
        $this->getEventManager()->trigger(__FUNCTION__ , $this,  compact('job','queue'));

        if ($job instanceof EventManagerAwareInterface){
          $job->setEventManager($this->getEventManager());
        }

        try {
            $job->execute();
            $queue->delete($job);
            $this->getEventManager()->trigger(__FUNCTION__ .'.post', $this, compact('job','queue'));
        } catch(JobException\ReleasableException $exception) {
            $this->getEventManager()->trigger(__FUNCTION__ . '.release', $this, compact('job', 'queue'));
            $queue->release($job, $exception->getOptions());
        } catch (JobException\BuryableException $exception) {
            $this->getEventManager()->trigger(__FUNCTION__ . '.bury', $this, compact('job', 'queue'));
            $queue->bury($job, $exception->getOptions());
        } catch (Exception $exception) {
            $this->getEventManager()->trigger(__FUNCTION__ . '.bury', $this, compact('job', 'queue'));
            $queue->bury($job, array('priority' => Pheanstalk::DEFAULT_PRIORITY));
        }
    }
}
