<?php

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