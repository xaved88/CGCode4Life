<?php

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