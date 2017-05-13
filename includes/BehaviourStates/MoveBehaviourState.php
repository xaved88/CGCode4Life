<?php

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