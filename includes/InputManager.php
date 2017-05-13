<?php

class InputManager
{
    /**
     * @deprecated
     * @return array
     */
    public function getProjectData()
    {
        fscanf(STDIN, "%d", $projectCount);
        for ($i = 0; $i < $projectCount; $i++) {
            $data = fscanf(STDIN, "%d %d %d %d %d");
            list($a, $b, $c, $d, $e) = $data;
        }

        return [];
    }

    /**
     * @return mixed[]
     */
    public function getTurnData()
    {
        $turnData           = [];
        $turnData['robots'] = [];
        for ($i = 0; $i < 2; $i++) {
            $data                 = fscanf(STDIN, "%s %d %d %d %d %d %d %d %d %d %d %d %d");
            $turnData['robots'][] = new Robot($data, $i);
        }

        list($a, $b, $c, $d, $e) = fscanf(STDIN, "%d %d %d %d %d");
        $turnData['molStorage'] = new MolBag($a, $b, $c, $d, $e);

        fscanf(STDIN, "%d", $turnData['sampleCount']);

        $turnData['samples'] = [];

        for ($i = 0; $i < $turnData['sampleCount']; $i++) {
            $data                  = fscanf(STDIN, "%d %d %d %s %d %d %d %d %d %d");
            $turnData['samples'][] = new Sample($data);
        }

        return $turnData;
    }
}