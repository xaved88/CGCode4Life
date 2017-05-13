<?php

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