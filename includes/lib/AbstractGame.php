<?php

abstract class AbstractGame
{
    const MAX_LOOPS = 0;

    /**
     * @var int
     */
    protected $gameLoopCount = 0;

    /**
     * @var GameOutput
     */
    protected $output;
    /**
     * @var InputManager
     */
    protected $inputManager;

    public function __construct()
    {
        $this->output       = new GameOutput();
        $this->inputManager = new InputManager();

        $this->preLoadLogic();
        $this->loadInitialData();
        $this->postLoadLogic();
    }

    public function runMainLoop()
    {
        while ($this->shouldRun()) {
            $this->loadTurnData();
            $this->turnLogic();
            $this->output->execute();
            $this->gameLoopCount++;
        }
    }

    protected function preLoadLogic()
    {

    }

    protected function loadInitialData()
    {
    }

    protected function postLoadLogic()
    {
    }

    protected function loadTurnData()
    {
    }

    abstract protected function turnLogic();

    private function shouldRun()
    {
        return static::MAX_LOOPS === 0 || $this->gameLoopCount < static::MAX_LOOPS;
    }
}