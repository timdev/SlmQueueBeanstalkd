<?php

namespace SlmQueueBeanstalkd\Job\Exception;

use RuntimeException;

/**
 * BuryableException
 */
class BuryableException extends RuntimeException
{
    /**
     * @var array
     */
    protected $options;


    /**
     * Valid option is:
     *      - priority: the lower the priority is, the sooner the job get kicked
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
      foreach(array('message'=>'','code'=>0,'previous'=>null) as $key=>$default){
        $$key = isset($options[$key]) ? $options[$key] : $default;
      }
      parent::__construct($message, $code, $previous);

      $this->options = $options;
    }

    /**
     * Get the options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
