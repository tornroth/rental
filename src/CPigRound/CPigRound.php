<?php
/**
 * A CPigRound class to play a round of pig.
 *
 */
abstract class CPigRound extends CPigDice
{

    // Properties
    private $roundAlive = false;
    private $rollsOfRound = array();


    // Get rolls of this round
    protected function GetRollsOfRound()
    {
        return $this->rollsOfRound;
    }


    // GetSumOfRound()
    protected function GetSumOfRound()
    {
        return array_sum($this->rollsOfRound);
    }


    // Another roll
    protected function RollDice()
    {
        if (!$this->roundAlive) {
            $this->rollsOfRound = array();
            $this->roundAlive = true;
        }
        $this->rollsOfRound[] = parent::RollDice();
        if (end($this->rollsOfRound) == 1) {
            $this->roundAlive = false;
        }
        return end($this->rollsOfRound);
    }


    // Stop active round
    protected function StopRound()
    {
        $this->roundAlive = false;
    }


    // View dices
    protected function ViewDices()
    {
        $strRolls = "<ul class='dice'>\n";
        foreach ($this->rollsOfRound as $value) {
            $strRolls .= parent::ViewDice($value);
        }
        $strRolls .= "</ul>\n";
        return $strRolls;
    }
}
