<?php

class GameOutput{
    /**
     * @var string
     */
    private $outputString = '';

    /**
     * @param string $data
     */
    public function append($data){
        $this->outputString .= $data;
    }

    public function execute(){
        echo $this->outputString . PHP_EOL;
        $this->outputString = '';
    }
}