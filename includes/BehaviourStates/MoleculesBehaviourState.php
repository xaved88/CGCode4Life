<?php

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