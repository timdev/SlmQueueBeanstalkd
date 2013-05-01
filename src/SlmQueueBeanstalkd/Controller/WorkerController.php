<?php

namespace SlmQueueBeanstalkd\Controller;

use Exception;
use SlmQueueBeanstalkd\Job\Exception\BuryableException;
use SlmQueueBeanstalkd\Job\Exception\ReleasableException;
use Zend\Mvc\Controller\AbstractActionController;

/**
 * This controller allow to execute jobs using the command line
 */
class WorkerController extends AbstractActionController
{
    /**
     * Process the queue given in parameter
     */
    public function processAction()
    {
        /** @var $worker \SlmQueueBeanstalkd\Worker\Worker */
        $worker    = $this->serviceLocator->get('SlmQueueBeanstalkd\Worker\Worker');
        $queueName = $this->params('queueName');
        $options   = array(
            'timeout' => $this->params('timeout', null)
        );

        $count = $worker->processQueue($queueName, array_filter($options));

        return sprintf(
            "\nWork for queue %s is done, %s jobs were processed\n\n",
            $queueName,
            $count
        );
    }
}
