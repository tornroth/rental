<?php
/**
 * A CPigDice class to a dice in a round of pig.
 *
 */
abstract class CPigDice
{

    // Roll the dice
    protected function RollDice()
    {
        return rand(1, 6);
    }

    // View a dice
    protected function ViewDice($dice)
    {
        return "<li class='dice-$dice'></li>";
    }
}
