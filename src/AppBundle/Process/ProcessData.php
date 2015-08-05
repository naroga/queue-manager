<?php

namespace AppBundle\Process;
use Symfony\Component\Process\Process;

/**
 * Class ProcessData
 * @package AppBundle\Process
 */
class ProcessData
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var Process
     */
    protected $process;

    /**
     * Class constructor
     *
     * @param Process $process
     * @param null $name
     */
    public function __construct(Process $process, $name = null)
    {
        $this->setName($name);
        $this->setProcess($process);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        if (!$name) {
            $name = substr(str_shuffle(md5(microtime())), 0, 10);
        }
        $this->name = $name;
    }

    /**
     * @return Process
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * @param Process $process
     */
    public function setProcess($process)
    {
        $this->process = $process;
    }


}
