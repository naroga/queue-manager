<?php

namespace AppBundle\Process;

use Symfony\Component\Process\Process as SymfonyProcess;

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
     * @param SymfonyProcess $process
     * @param null $name
     */
    public function __construct(SymfonyProcess $process, $name = null)
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
        $this->name = $name;
    }

    /**
     * @return SymfonyProcess
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * @param SymfonyProcess $process
     */
    public function setProcess($process)
    {
        $this->process = $process;
    }


}
