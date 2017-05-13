<?php

class Samples extends AbstractModule
{
    const NAME = "SAMPLES";

    /**
     * @var Sample[];
     */
    public $samples;

    /**
     * @return Sample
     */
    public function getBestSampleInCloud()
    {
        $bestValue  = 0;
        $bestSample = null;
        if (!empty($this->samples)) {
            foreach ($this->samples as $sample) {
                if ($sample->ownerId !== MainGame::CLOUD) {
                    continue;
                }

                $value = $sample->getValue();
                if ($value > $bestValue && $sample->isGood()) {
                    $bestValue  = $value;
                    $bestSample = $sample;
                }
            }
        }

        return $bestSample;
    }

    public function getDiagnosedSamples()
    {
        return array_filter($this->samples, function ($sample) {
            /** @var Sample $sample */
            return $sample->isDiagnosed();
        });
    }

    public function isGoodStuffInCloud()
    {
        if (!empty($this->samples)) {
            foreach ($this->samples as $sample) {
                if ($sample->ownerId !== MainGame::CLOUD) {
                    continue;
                }

                if ($sample->isGood()) {
                    return true;
                }
            }
        }

        return false;
    }
}