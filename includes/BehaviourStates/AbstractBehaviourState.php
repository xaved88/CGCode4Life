<?php

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