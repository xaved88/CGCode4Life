<?php

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