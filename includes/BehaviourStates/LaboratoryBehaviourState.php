<?php

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