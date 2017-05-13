<?php

class Molecules extends AbstractModule
{
    const NAME = "MOLECULES";

    const TYPE_A = "A";
    const TYPE_B = "B";
    const TYPE_C = "C";
    const TYPE_D = "D";
    const TYPE_E = "E";


    /**
     * @var MolBag
     */
    public $storage;

    public function __construct()
    {
        $this->storage = new MolBag();
    }

    public function setStorageValues($a, $b, $c, $d, $e)
    {
        $this->storage->updateAll($a, $b, $c, $d, $e);
    }

    /**
     * @param $molBag
     */
    public function setStorage($molBag)
    {
        $this->storage = $molBag;
    }
}