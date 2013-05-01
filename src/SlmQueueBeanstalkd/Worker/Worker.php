<?php

namespace SlmQueueBeanstalkd\Worker;

use Exception;
use Pheanstalk_Pheanstalk as Pheanstalk;
use SlmQueue\Job\JobInterface;
use SlmQueue\Queue\QueueInterface;
use SlmQueue\Worker\AbstractWorker;
use SlmQueueBeanstalkd\Job\Exception as JobException;
use SlmQueueBeanstalkd\Queue\TubeInterface;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;

/**
 * Worker for Beanstalkd
 */
class Worker extends AbstractWorker implements EventManagerAwareInterface {
  /**
   * @var EventManagerInterface
   */
  protected $events;

  /**
   * {@inheritDoc}
   */
  public function processJob(JobInterface $job, QueueInterface $queue) {
    if (!$queue instanceof TubeInterface) {
      return;
    }

    $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, compact('job', 'queue'));

    try {
      $job->execute();
      $queue->delete($job);
      $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, compact('job', 'queue'));
    } catch (JobException\ReleasableException $exception) {
      $this->getEventManager()->trigger(__FUNCTION__ . '.release', $this, compact('job', 'queue', 'exception'));
      $queue->release($job, $exception->getOptions());
    } catch (JobException\BuryableException $exception) {
      $this->getEventManager()->trigger(__FUNCTION__ . '.bury', $this, compact('job', 'queue', 'exception'));
      $queue->bury($job, $exception->getOptions());
    } catch (Exception $exception) {
      $this->getEventManager()->trigger(__FUNCTION__ . '.bury', $this, compact('job', 'queue', 'exception'));
      $queue->bury($job, array('priority' => Pheanstalk::DEFAULT_PRIORITY));
    }
  }

  /**
   * Retrieve the event manager
   *
   * Lazy-loads an EventManager instance if none registered.
   *
   * @return EventManagerInterface
   */
  public function getEventManager() {
    if (!$this->events instanceof EventManagerInterface) {
      $this->setEventManager(new EventManager());
    }
    return $this->events;
  }

  /**
   * Set the event manager instance used by this context
   *
   * @param  EventManagerInterface $events
   * @return mixed
   */
  public function setEventManager(EventManagerInterface $events) {
    $identifiers = array(__CLASS__, get_class($this));
    if (isset($this->eventIdentifier)) {
      if ((is_string($this->eventIdentifier))
        || (is_array($this->eventIdentifier))
        || ($this->eventIdentifier instanceof Traversable)
      ) {
        $identifiers = array_unique(array_merge($identifiers, (array)$this->eventIdentifier));
      } elseif (is_object($this->eventIdentifier)) {
        $identifiers[] = $this->eventIdentifier;
      }
      // silently ignore invalid eventIdentifier types
    }
    $events->setIdentifiers($identifiers);
    $this->events = $events;
    return $this;
  }
}
