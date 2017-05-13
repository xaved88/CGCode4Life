<?php

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