<?php
        

function elog($var)
{
    error_log(var_export($var, true));
}

abstract class AbstractGame
{
    const MAX_LOOPS = 0;

    /**
     * @var int
     */
    protected $gameLoopCount = 0;

    /**
     * @var GameOutput
     */
    protected $output;
    /**
     * @var InputManager
     */
    protected $inputManager;

    public function __construct()
    {
        $this->output       = new GameOutput();
        $this->inputManager = new InputManager();

        $this->preLoadLogic();
        $this->loadInitialData();
        $this->postLoadLogic();
    }

    public function runMainLoop()
    {
        while ($this->shouldRun()) {
            $this->loadTurnData();
            $this->turnLogic();
            $this->output->execute();
            $this->gameLoopCount++;
        }
    }

    protected function preLoadLogic()
    {

    }

    protected function loadInitialData()
    {
    }

    protected function postLoadLogic()
    {
    }

    protected function loadTurnData()
    {
    }

    abstract protected function turnLogic();

    private function shouldRun()
    {
        return static::MAX_LOOPS === 0 || $this->gameLoopCount < static::MAX_LOOPS;
    }
}

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


class Robot
{
    const MAX_SAMPLES = 3;

    /**
     * @var int
     */
    public $ownerId;

    /**
     * @var string
     */
    public $target;

    /**
     * @var int
     * @deprecated
     */
    public $eta;

    /**
     * @var int
     */
    public $score;

    /**
     * @var MolBag
     */
    public $storage;

    /**
     * @var MolBag
     */
    public $expertise;

    /**
     * @var Sample[]
     */
    public $samples = [];

    /**
     * @var AbstractBehaviourState
     */
    public $behaviourState;

    /**
     * @param array $data
     * @param int   $ownerId
     */
    public function __construct($data, $ownerId)
    {
        list(
            $target,
            $eta,
            $score,
            $storageA,
            $storageB,
            $storageC,
            $storageD,
            $storageE,
            $expA,
            $expB,
            $expC,
            $expD,
            $expE
            ) = $data;

        $this->ownerId   = $ownerId;
        $this->target    = $target;
        $this->eta       = $eta;
        $this->score     = $score;
        $this->storage   = new MolBag($storageA, $storageB, $storageC, $storageD, $storageE);
        $this->expertise = new MolBag($expA, $expB, $expC, $expD, $expE);
    }

    /**
     * @param Samples   $samples
     * @param Molecules $molecules
     *
     * @return null|string
     */

    public function getAction($samples, $molecules)
    {
        $this->behaviourState = BehaviourFactory::makeBehaviourState($this, $samples, $molecules);
        $action               = $this->behaviourState->getAction();

        if (null === $action) {
            $this->behaviourState = BehaviourFactory::makeDefaultBehaviourState($this, $samples, $molecules);
            $action               = $this->behaviourState->getAction();
        }

        return $action;
    }

    /**
     * @return bool
     */
    public function hasSamples()
    {
        return !empty($this->samples);
    }

    public function canCarryMoreSamples()
    {
        return count($this->samples) < static::MAX_SAMPLES;
    }

    public function hasCompleteSamples()
    {
        return null !== $this->getCompleteSample();
    }

    public function hasUndiagnosedSamples()
    {
        foreach ($this->samples as $sample) {
            if (!$sample->isDiagnosed()) {
                return true;
            }
        }

        return false;
    }

    public function hasGoodDiagnosedSamples()
    {
        foreach ($this->samples as $sample) {
            if ($sample->isDiagnosed() && $sample->isGood()) {
                return true;
            }
        }

        return false;
    }

    public function hasCrapSamples()
    {
        return null !== $this->getCrapSample();
    }

    /**
     * @return null|Sample
     */
    public function getCrapSample()
    {
        foreach ($this->samples as $sample) {
            if ($sample->isDiagnosed() && !$sample->isGood()) {
                return $sample;
            }
        }

        return null;
    }

    public function getCompleteSample()
    {
        $completeSample = null;
        if (!empty($this->samples)) {
            foreach ($this->samples as $sample) {
                if ($this->storage->contains($sample->cost)) {
                    $completeSample = $sample;
                    break;
                }
            }
        }

        return $completeSample;
    }

    public function getSampleProgress()
    {
        $sample = reset($this->samples);

        $missingCost = $this->storage->getMissing($sample->cost);
        $missingIds  = array_flip($missingCost);

        return reset($missingIds);
    }

    /**
     * @param string $location
     *
     * @return bool
     */
    public function isAt($location)
    {
        return $this->target === $location;
    }


    public function makeGoCommand($location)
    {
        return "GOTO " . $location;
    }

    public function makeConnectCommand($var)
    {
        return "CONNECT " . $var;
    }
}

class MolBag
{
    /**
     * @var array (moleculeType => amount)
     */
    public $mol;

    public function __construct($a = 0, $b = 0, $c = 0, $d = 0, $e = 0)
    {
        $this->updateAll($a, $b, $c, $d, $e);
    }

    public function updateAll($a, $b, $c, $d, $e)
    {
        $this->mol = [
            Molecules::TYPE_A => $a,
            Molecules::TYPE_B => $b,
            Molecules::TYPE_C => $c,
            Molecules::TYPE_D => $d,
            Molecules::TYPE_E => $e,
        ];
    }

    public function getTotal()
    {
        return array_sum($this->mol);
    }

    /**
     * @param MolBag $molBag
     *
     * @return bool
     */
    public function contains($molBag)
    {
        foreach ($this->mol as $key => $value) {
            if ($molBag->mol[$key] > $value) {
                return false;
            }
        }

        return true;
    }

    public function getMissing($molBag){
        $missing = [];
        foreach ($this->mol as $key => $value) {
            if ($molBag->mol[$key] > $value) {
                $missing[$key] = $molBag->mol[$key] - $value;
            }
        }

        return $missing;
    }
}

class Samples extends AbstractModule
{
    const NAME = "SAMPLES";

    /**
     * @var Sample[];
     */
    public $samples;

    /**
     * @return Sample
     */
    public function getBestSampleInCloud()
    {
        $bestValue  = 0;
        $bestSample = null;
        if (!empty($this->samples)) {
            foreach ($this->samples as $sample) {
                if ($sample->ownerId !== MainGame::CLOUD) {
                    continue;
                }

                $value = $sample->getValue();
                if ($value > $bestValue && $sample->isGood()) {
                    $bestValue  = $value;
                    $bestSample = $sample;
                }
            }
        }

        return $bestSample;
    }

    public function getDiagnosedSamples()
    {
        return array_filter($this->samples, function ($sample) {
            /** @var Sample $sample */
            return $sample->isDiagnosed();
        });
    }

    public function isGoodStuffInCloud()
    {
        if (!empty($this->samples)) {
            foreach ($this->samples as $sample) {
                if ($sample->ownerId !== MainGame::CLOUD) {
                    continue;
                }

                if ($sample->isGood()) {
                    return true;
                }
            }
        }

        return false;
    }
}

class Molecules extends AbstractModule
{
    const NAME = "MOLECULES";

    const TYPE_A = "A";
    const TYPE_B = "B";
    const TYPE_C = "C";
    const TYPE_D = "D";
    const TYPE_E = "E";


    /**
     * @var MolBag
     */
    public $storage;

    public function __construct()
    {
        $this->storage = new MolBag();
    }

    public function setStorageValues($a, $b, $c, $d, $e)
    {
        $this->storage->updateAll($a, $b, $c, $d, $e);
    }

    /**
     * @param $molBag
     */
    public function setStorage($molBag)
    {
        $this->storage = $molBag;
    }
}

class Laboratory extends AbstractModule
{
    const NAME = "LABORATORY";

}

class Diagnosis extends AbstractModule
{
    const NAME = "DIAGNOSIS";

}

abstract class AbstractModule
{

}

class BehaviourFactory
{
    const StateObjectSuffix = 'BehaviourState';
    const DEFAULT_BEHAVIOUR = 'Move';

    /**
     * @param Robot     $robot
     * @param Samples   $samples
     * @param Molecules $molecules
     *
     * @return AbstractBehaviourState
     */
    static function makeBehaviourState($robot, $samples, $molecules)
    {
        $location = ucfirst($robot->target);

        return static::makeBehaviourStateFromCommand($location, $robot, $samples, $molecules);
    }

    /**
     * @param Robot     $robot
     * @param Samples   $samples
     * @param Molecules $molecules
     *
     * @return AbstractBehaviourState
     */
    static function makeDefaultBehaviourState($robot, $samples, $molecules)
    {
        return static::makeBehaviourStateFromCommand(static::DEFAULT_BEHAVIOUR, $robot, $samples, $molecules);
    }

    /**
     * @param string    $commandName
     * @param Robot     $robot
     * @param Samples   $samples
     * @param Molecules $molecules
     *
     * @return AbstractBehaviourState
     * @throws Exception
     */
    static function makeBehaviourStateFromCommand($commandName, $robot, $samples, $molecules)
    {
        $className = $commandName . static::StateObjectSuffix;
        if (class_exists($className)) {
            return new $className($robot, $samples, $molecules);
        } elseif($commandName !== static::DEFAULT_BEHAVIOUR) {
            return static::makeDefaultBehaviourState($robot, $samples, $molecules);
        } else {
            throw new Exception(sprintf('Undefined behaviour state for %s', $commandName));
        }

    }
}

abstract class AbstractBehaviourState
{

    /**
     * @var Robot - yes I know it's circular dependency, so sue me...
     */
    public $robot;

    /**
     * @var Samples
     */
    public $samples;

    /**
     * @var Molecules
     */
    public $molecules;

    /**
     * @param Robot     $robot
     * @param Samples   $samples
     * @param Molecules $molecules
     *
     * @return null|AbstractBehaviourState
     */
    public function __construct($robot, $samples, $molecules)
    {
        $this->robot     = $robot;
        $this->samples   = $samples;
        $this->molecules = $molecules;
    }

    /**
     * If it's a location behavior state, it will determine what the robot should do while it is at the location.
     * If there isn't a good usable action to do there, it should return null, and then a new acction state for moving
     * on should be created by the robot.
     *
     * @return string|null
     */
    abstract public function getAction();
}

class GameOutput{
    /**
     * @var string
     */
    private $outputString = '';

    /**
     * @param string $data
     */
    public function append($data){
        $this->outputString .= $data;
    }

    public function execute(){
        echo $this->outputString . PHP_EOL;
        $this->outputString = '';
    }
}

class InputManager
{
    /**
     * @deprecated
     * @return array
     */
    public function getProjectData()
    {
        fscanf(STDIN, "%d", $projectCount);
        for ($i = 0; $i < $projectCount; $i++) {
            $data = fscanf(STDIN, "%d %d %d %d %d");
            list($a, $b, $c, $d, $e) = $data;
        }

        return [];
    }

    /**
     * @return mixed[]
     */
    public function getTurnData()
    {
        $turnData           = [];
        $turnData['robots'] = [];
        for ($i = 0; $i < 2; $i++) {
            $data                 = fscanf(STDIN, "%s %d %d %d %d %d %d %d %d %d %d %d %d");
            $turnData['robots'][] = new Robot($data, $i);
        }

        list($a, $b, $c, $d, $e) = fscanf(STDIN, "%d %d %d %d %d");
        $turnData['molStorage'] = new MolBag($a, $b, $c, $d, $e);

        fscanf(STDIN, "%d", $turnData['sampleCount']);

        $turnData['samples'] = [];

        for ($i = 0; $i < $turnData['sampleCount']; $i++) {
            $data                  = fscanf(STDIN, "%d %d %d %s %d %d %d %d %d %d");
            $turnData['samples'][] = new Sample($data);
        }

        return $turnData;
    }
}

class SamplesBehaviourState extends AbstractBehaviourState
{
    public function getAction()
    {
        if ($this->robot->canCarryMoreSamples()) {
            return $this->makeTakeSampleCommand();
        }

        return null;
    }


    private function makeTakeSampleCommand()
    {
        $ownedRanks = [];
        foreach ($this->robot->samples as $sample) {
            $ownedRanks[] = $sample->rank;
        }

        $hasRankThree = in_array(3, $ownedRanks);
        $hasRankTwo   = in_array(2, $ownedRanks);

        if (!$hasRankThree || $hasRankTwo) {
            $getRank = 3;
        } else {
            $getRank = 2;
        }

        return $this->robot->makeConnectCommand($getRank);
    }
}

class MoveBehaviourState extends AbstractBehaviourState
{
    public function getAction()
    {
        // NO SAMPLES, NO GOOD STUFF IN CLOUD
        if (!$this->robot->hasSamples() && !$this->samples->isGoodStuffInCloud()) {

            return $this->robot->makeGoCommand(Samples::NAME);
        }

        // GO TO THE DIAGNOSIS IF NOT THERE AND DOESNT HAVE GOOD DIAGNOSED SAMPLES
        if (!$this->robot->isAt(Diagnosis::NAME) && !$this->robot->hasGoodDiagnosedSamples()) {
            return $this->robot->makeGoCommand(Diagnosis::NAME);
        }

        // IF ROBOT HAS NO COMPLETE SAMPLES, GO TO MOLECULES
        if (!$this->robot->hasCompleteSamples() && !$this->robot->isAt(Molecules::NAME)) {

            return $this->robot->makeGoCommand(Molecules::NAME);
        }

        // IF ROBOT HAS A COMPLETE SAMPLE, GO TO LAB
        if (!$this->robot->isAt(Laboratory::NAME) && $this->robot->hasCompleteSamples()) {

            return $this->robot->makeGoCommand(Laboratory::NAME);
        }

        throw new Exception('wtf this default state cant find anything to do');
    }
}

class MoleculesBehaviourState extends AbstractBehaviourState
{
    public function getAction()
    {
        if (!$this->robot->hasCompleteSamples()) {
            $progress = $this->robot->getSampleProgress();

            return $this->robot->makeConnectCommand($progress);
        }

        return null;
    }
}

class LaboratoryBehaviourState extends AbstractBehaviourState
{
    public function getAction()
    {
        if ($this->robot->hasCompleteSamples()) {
            $completedSample = $this->robot->getCompleteSample();

            return $this->robot->makeConnectCommand($completedSample->id);
        }

        return null;
    }
}

class DiagnosisBehaviourState extends AbstractBehaviourState
{
    public function getAction()
    {
        if ($this->robot->canCarryMoreSamples() && $this->samples->isGoodStuffInCloud()) {
            $sample = $this->samples->getBestSampleInCloud();

            return $this->robot->makeConnectCommand($sample->id);
        }

        // HAS THINGS TO DIAGNOSE
        if ($this->robot->hasUndiagnosedSamples()) {
            return $this->makeDiagnoseSampleCommand();
        }

        // HAS CRAP TO DROP OFF
        if ($this->robot->hasCrapSamples()) {
            return $this->makeDropCrapSampleCommand();
        }

        return null;
    }

    private function makeDropCrapSampleCommand()
    {
        $sample = $this->robot->getCrapSample();

        return $this->robot->makeConnectCommand($sample->id);
    }

    private function makeDiagnoseSampleCommand()
    {
        foreach ($this->robot->samples as $sample) {
            if (!$sample->isDiagnosed()) {
                return $this->robot->makeConnectCommand($sample->id);
            }
        }

        throw new Exception('No samples to diagnose!');
    }
}

class Sample
{

    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $ownerId;

    /**
     * @var int
     */
    public $rank;

    /**
     * @deprecated
     */
    public $expertiseGain;

    /**
     * @var int
     */
    public $health;

    /**
     * @var MolBag
     */
    public $cost;

    /**
     * @param array $data
     */
    public function __construct($data)
    {
        list(
            $sampleId,
            $carriedBy,
            $rank,
            $expertiseGain,
            $health,
            $costA,
            $costB,
            $costC,
            $costD,
            $costE
            ) = $data;

        $this->id            = $sampleId;
        $this->ownerId       = $carriedBy;
        $this->rank          = $rank;
        $this->expertiseGain = $expertiseGain;
        $this->health        = $health;
        $this->cost          = new MolBag($costA, $costB, $costC, $costD, $costE);
    }


    public function getValue()
    {
        $totalCost = $this->cost->getTotal();
        if ($totalCost <= 0) {
            return $this->health;
        }

        return $this->health / $totalCost;
    }

    public function isGood()
    {
        return $this->getValue() >= 2.5 && $this->cost->getTotal() <= 10;
    }

    public function isDiagnosed()
    {
        return $this->health !== -1;
    }
}

try {
    $game = new MainGame();
    $game->runMainLoop();
}
catch (Exception $exception){
    elog($exception->getMessage());
}