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
        $this->initSamples();

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

    private function initSamples()
    {

    }

    /**
     * @return string
     * @throws Exception
     */
    private function whatMyRobotDo()
    {
        $robot = $this->myRobot;

        // AT SAMPLES AND NOT FULL - PICK THEM UP
        if ($robot->isAt(Samples::NAME) && $robot->canCarryMoreSamples()) {
            return $robot->makeTakeSampleCommand();
        }

        // NO SAMPLES, NO GOOD STUFF IN CLOUD
        if (!$robot->hasSamples() && !$this->samples->isGoodStuffInCloud()) {

            return $robot->makeGoCommand(Samples::NAME);
        }

        // AT THE DIAGNOSIS
        if ($robot->isAt(Diagnosis::NAME)) {
            // GOOD STUFF TO PICK UP
            if ($robot->canCarryMoreSamples() && $this->samples->isGoodStuffInCloud()) {
                $sample = $this->samples->getBestSampleInCloud();

                return $robot->makeConnectCommand($sample->id);
            }

            // HAS THINGS TO DIAGNOSE
            if ($robot->hasUndiagnosedSamples()) {
                return $robot->makeDiagnoseSampleCommand();
            }

            // HAS CRAP TO DROP OFF
            if ($robot->hasCrapSamples()) {
                return $robot->makeDropCrapSampleCommand();
            }
        }

        // GO TO THE DIAGNOSIS IF NOT THERE AND DOESNT HAVE GOOD DIAGNOSED SAMPLES
        if (!$robot->isAt(Diagnosis::NAME) && !$robot->hasGoodDiagnosedSamples()) {
            return $robot->makeGoCommand(Diagnosis::NAME);
        }

        // IF ROBOT HAS NO COMPLETE SAMPLES, GO TO MOLECULES
        if (!$robot->hasCompleteSamples() && !$robot->isAt(Molecules::NAME)) {

            return $robot->makeGoCommand(Molecules::NAME);
        }


        // IF ROBOT IS AT MOLECULES, DETERMINE BEST SAMPLE TO COMPLETE AND PROGRESS ON IT
        if ($robot->isAt(Molecules::NAME) && !$robot->hasCompleteSamples()) {
            $progress = $robot->getSampleProgress();

            return $robot->makeConnectCommand($progress);
        }


        // IF ROBOT HAS A COMPLETE SAMPLE, GO TO LAB
        if (!$robot->isAt(Laboratory::NAME) && $robot->hasCompleteSamples()) {

            return $robot->makeGoCommand(Laboratory::NAME);
        }

        // IF ROBOT IS AT LAB AND HAS COMPLETE SAMPLE, FINISH IT
        if ($robot->isAt(Laboratory::NAME) && $robot->hasCompleteSamples()) {
            $completedSample = $robot->getCompleteSample();

            return $robot->makeConnectCommand($completedSample->id);
        }

        throw new Exception('Got nothing to do!');
    }

}
