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


    private $cachedCompleteSample = false;

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
        return (bool)$this->getCrapSample();
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
        if ($this->cachedCompleteSample !== false) {
            return $this->cachedCompleteSample;
        }

        $completeSample = null;
        if (!empty($this->samples)) {
            foreach ($this->samples as $sample) {
                if ($this->storage->contains($sample->cost)) {
                    $completeSample = $sample;
                    break;
                }
            }
        }

        $this->cachedCompleteSample = $completeSample;

        return $completeSample;
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

    public function getSampleProgress()
    {
        $sample = reset($this->samples);

        $missingCost = $this->storage->getMissing($sample->cost);
        $missingIds  = array_flip($missingCost);

        return reset($missingIds);
    }


    public function makeGoCommand($location)
    {
        return "GOTO " . $location;
    }

    public function makeConnectCommand($var)
    {
        return "CONNECT " . $var;
    }

    public function makeTakeSampleCommand()
    {
        $ownedRanks = [];
        foreach ($this->samples as $sample) {
            $ownedRanks[] = $sample->rank;
        }

        $hasRankThree = in_array(3, $ownedRanks);
        $hasRankTwo   = in_array(2, $ownedRanks);

        if (!$hasRankThree || $hasRankTwo) {
            $getRank = 3;
        } else {
            $getRank = 2;
        }

        return $this->makeConnectCommand($getRank);
    }

    public function makeDropCrapSampleCommand()
    {
        $sample = $this->getCrapSample();

        return $this->makeConnectCommand($sample->id);
    }

    public function makeDiagnoseSampleCommand()
    {
        foreach ($this->samples as $sample) {
            if (!$sample->isDiagnosed()) {
                return $this->makeConnectCommand($sample->id);
            }
        }

        throw new Exception('No samples to diagnose!');
    }
}