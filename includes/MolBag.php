<?php

class MolBag
{
    /**
     * @var array (moleculeType => amount)
     */
    public $mol;

    public function __construct($a = 0, $b = 0, $c = 0, $d = 0, $e = 0)
    {
        $this->updateAll($a, $b, $c, $d, $e);
    }

    public function updateAll($a, $b, $c, $d, $e)
    {
        $this->mol = [
            Molecules::TYPE_A => $a,
            Molecules::TYPE_B => $b,
            Molecules::TYPE_C => $c,
            Molecules::TYPE_D => $d,
            Molecules::TYPE_E => $e,
        ];
    }

    public function getTotal()
    {
        return array_sum($this->mol);
    }

    /**
     * @param MolBag $molBag
     *
     * @return bool
     */
    public function contains($molBag)
    {
        foreach ($this->mol as $key => $value) {
            if ($molBag->mol[$key] > $value) {
                return false;
            }
        }

        return true;
    }

    public function getMissing($molBag){
        $missing = [];
        foreach ($this->mol as $key => $value) {
            if ($molBag->mol[$key] > $value) {
                $missing[$key] = $molBag->mol[$key] - $value;
            }
        }

        return $missing;
    }
}