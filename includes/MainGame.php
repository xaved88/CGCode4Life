<?php

class MainGame extends AbstractGame
{
    const ME    = 0;
    const ENEMY = 1;
    const CLOUD = -1;

    /**
     * @var Diagnosis
     */
    private $diagnosis;
    /**
     * @var Molecules
     */
    private $molecules;

    /**
     * @var Laboratory
     */
    private $laboratory;

    /**
     * @var Robot
     */
    private $myRobot;

    /**
     * @var Robot[]
     */
    private $robots = [];

    /**
     * @var Samples
     */
    private $samples;

    /////////////////////////////////////////////
    ////        CORE FUNCTIONALITY          /////
    /////////////////////////////////////////////

    protected function preLoadLogic()
    {
        $this->diagnosis  = new Diagnosis();
        $this->molecules  = new Molecules();
        $this->laboratory = new Laboratory();
        $this->samples    = new Samples();
    }

    protected function loadInitialData()
    {
        $projectData = $this->inputManager->getProjectData();
    }

    protected function loadTurnData()
    {
        $this->myRobot = null;

        $turnData = $this->inputManager->getTurnData();

        $this->robots           = $turnData['robots'];
        $this->samples->samples = $turnData['samples'];
        $this->molecules->setStorage($turnData['molStorage']);
    }

    protected function turnLogic()
    {
        $this->initMyRobot();

        $output = $this->whatMyRobotDo();

        $this->output->append($output);
    }

    /////////////////////////////////////////////
    ////      GAME UNIQUE FUNCTIONALITY     /////
    /////////////////////////////////////////////

    private function initMyRobot()
    {
        foreach ($this->robots as $robot) {
            if ($robot->ownerId === static::ME) {
                $this->myRobot = $robot;
            }
        }

        if ($this->myRobot === null) {
            throw new Exception('I cant find your robot!');
        }

        foreach ($this->samples->samples as $sample) {
            if ($sample->ownerId === static::ME) {
                $this->myRobot->samples[] = $sample;
            }
        }
    }

    /**
     * @return string
     */
    private function whatMyRobotDo()
    {
        $robot = $this->myRobot;

        return $robot->getAction($this->samples, $this->molecules);
    }

}
