<?php
/**
 * A CPigGame class to play a game of pig.
 *
 */
class CPigGame extends CPigRound
{

    // Properties
    private $double     = 0;
    private $computer   = 0;
    private $status     = array('start', 'wait');
    private $savedRolls = array(array(), array());
    private $msg        = array(array(
        'name'  => 'Spelare ',
        'start' => 'Varsågod att kasta!',
        'roll'  => 'Du fick en *ROLL*:a.<br />Fortsätt kasta eller stanna.',
        'one'   => 'Rackarns, en etta...<br />Du förlorade dina poäng den här rundan.',
        'stop'  => 'Du stannade rundan.<br />Dina poäng har sparats.',
        // RM: 'win'   => 'Vi har en vinnare!<br />GRATTIS!',
        'win'   => 'GRATTIS! Du vann!<br />Nu kan du hämta ut en hyrfilm.',
        'lose'  => 'Du är besegrad!<br />Kämpa på bättre nästa gång.',
        // RM: 'wait'  => 'Motståndarens tur.<br />Vänligen vänta.'
        'wait'  => 'Vår tur.<br />Nu får du vänta.'
        ), array(
/*      RM: Changed some messages
        'name'  => 'Datorn',
        'start' => 'Datorn laddar upp.',
        'roll'  => 'Datorn fick en *ROLL*:a.<br />Funderar på nästa steg.',
        'one'   => 'Stackarn, en etta...<br />Datorn fick en tår i skärmen.',
        'stop'  => 'Datorn stannar och reflekterar.',
        'win'   => 'Lycka och CPU\'n på högvarv!<br />Datorn vann!',
        'lose'  => 'Datorn förlorade och funderar på virus som hämnd...',
        'wait'  => 'Datorn väntar på sin tur.'
*/
        'name'  => 'Rental Movies',
        'start' => 'Nu laddar vi. :)',
        'roll'  => 'Vi fick en *ROLL*:a.<br />Funderar på nästa steg.',
        'one'   => 'Stackars oss, en etta...<br />Buh-hu... *snörvel*',
        'stop'  => 'Vi stannar och reflekterar.',
        'win'   => 'Wohoo!<br />Vi vann, vi vann, vi vann!',
        'lose'  => '!#=%("#=@%',
        'wait'  => 'Vi väntar...'
        ));


    // Construct
    public function __construct($type)
    {
        $this->double = ($type == "double") ? 1 : 0;
        $this->computer = ($type == "computer") ? 1 : 0;
    }


    // Get infotext
    public function GetInfo()
    {
        if ($this->computer) {
            $info = 'Du har utmanat datorn. <a href="?new=double">Utmana</a> en vän eller <a href="?new=single">fega ur</a> om du vill spela själv.';
        }
        elseif ($this->double) {
            $info = 'Ni spelar mot varandra. Ingen kompis? <a href="?new=computer">Utmana datorn</a> eller <a href="?new=single">spela själv</a>.';
        }
        else {
            $info = 'Du spelar själv. När du har tränat färdigt kan du utmana <a href="?new=computer">datorn</a> eller <a href="?new=double">en vän</a>.';
        }
        // RM: Line below.
        $info = "Vinner du mot Rental Movies, så bjuder vi på en hyrfilm!";
        return $info;
    }


    // Function to render game plan
    public function GetGamePlan($player = 0)
    {
        $gp  = "<div id=pig$player>\n";
        $gp .= "<div class='" . self::GetClass($player) . "'>\n";
        $gp .= "<h2 class='pigname'>" . self::GetName($player) . "</h2>\n";
        $gp .= "<div class='pigsum'>" . self::GetSum($player) . "</div>\n";
        $gp .= "<div class='clear'></div>\n";
        $gp .= "<div class='pigmsg'>" . self::GetMsg($player) . "</div>\n";
        $gp .= "<div class='pigbtns'>" . self::GetBtns($player) . "</div>\n";
        $gp .= "<div class='pigdices'>" . self::ViewDices($player) . "</div>\n";
        $gp .= "</div></div>\n";
        return $gp;
    }

    // Get class
    private function GetClass($player)
    {
        return (self::IsPlayerActive($player)) ? "pigactive" : "pigwaiting";
    }


    // Get name of player
    private function GetName($player)
    {
        $name = ($player) ? $this->msg[$this->computer]['name'] : $this->msg[$player]['name'];
        $name .= ($this->double) ? $player+1 : "";
        return $name;
    }


    // Get current sum of finished rounds
    private function GetSum($player)
    {
        return array_sum($this->savedRolls[$player]);
    }


    // Get message
    private function GetMsg($player)
    {
        $stat = $this->status[$player];
        $msgArray = ($player && $this->double) ? $this->msg[0] : $this->msg[$player];
        if (is_numeric($stat)) {
            $msg = str_replace('*ROLL*', $stat, $msgArray['roll']);
        }
        else {
            $msg = $msgArray[$stat];
        }
        return $msg;
    }


    // Get buttons
    private function GetBtns($player)
    {
        $btns = "";
        if ((self::IsPlayerActive($player) && self::IsPlayerHuman($player)) || !(self::IsChallange())) {
            $stat = $this->status[$player];

            $btns  = "<form method='post'>\n";
            if (is_numeric($stat)) {
                $btns .= "<input type='submit' name='roll' value='Kasta' />\n";
                $btns .= "<input type='submit' name='stop' value='Stanna' />\n";
            }
            elseif ($stat == 'start' || $stat == 'one' || $stat == 'stop') {
                $btns .= "<input type='submit' name='roll' value='Kasta' />\n";
            }
            elseif ($stat == 'wait') {
                $btns .= "<input type='submit' name='roll' value='Vänta...' disabled='disabled'/>\n";
            }
            elseif ($stat == 'win' || $stat == 'lose') {
                if ($this->computer) {
                    $btns .= "<input type='submit' name='challange' value='Ny utmaning' />\n";
                }
                else {
                    $btns .= "<input type='submit' name='newgame' value='Starta om' />\n";
                }
                $type = (self::IsChallange()) ? (self::IsPlayerHuman(1) ? "double" : "computer") : "single";
                $btns .= "<input type='hidden' name='new' value='$type' />\n";
            }
            $btns .= "<input type='hidden' name='player' value='$player' />\n";
            $btns .= "</form>\n\n";
        }
        return $btns;
    }


    // Set status
    private function SetStatus($player = 0, $val)
    {
        if ($val == 1) {
            $val = 'one';
            $this->status[abs($player-1)] = 'start';
        }
        $this->status[$player] = $val;
    }


    // Return true if it's a challange game
    public function IsChallange()
    {
        return ($this->double || $this->computer);
    }


    // Return true if player is active
    private function IsPlayerActive($player)
    {
        return !(in_array($this->status[$player], array('wait', 'one', 'stop', 'lose')));
    }


    // Return true if player ain't the computer
    private function IsPlayerHuman($player)
    {
        return (abs($player*$this->computer-1));
    }

    // Return true if it's the computers turn
    public function IsComputersTurn()
    {
        return ($this->computer && ($this->status[1] == 'start' || is_numeric($this->status[1])));
    }


    // Calculate the computers move
    public function ComputersMove()
    {
        if ($this->status[1] == 'start' || ((rand(1, 6) > 1) && ($this->GetSum(1)+parent::GetSumOfRound() < 100))) {
            $this->RollDice(1);
        }
        else {
            $this->StopRound(1);
        }
    }


    // Roll dice
    public function RollDice($player = 0)
    {
        $this->SetStatus($player, parent::RollDice());
        if (self::IsChallange() && ($this->status[abs($player-1)] == 'stop' || $this->status[abs($player-1)] == 'one')) {
            $this->status[abs($player-1)] = 'wait';
        }
    }


    // Stop round and save rolls
    public function StopRound($player = 0)
    {
        if (is_numeric($this->status[$player])) {
            $this->savedRolls[$player] = array_merge($this->savedRolls[$player], parent::GetRollsOfRound());
        }
        parent::StopRound();
        $this->status[$player] = ($this->GetSum($player) < 100) ? 'stop' : 'win';
        $this->status[abs($player-1)] = ($this->GetSum($player) < 100) ? 'start' : 'lose';
    }


    // View dices
    protected function ViewDices($player = 0)
    {
        $dices  = (is_numeric($this->status[$player])) ? parent::ViewDices() : "";
        $dices .= ($this->status[$player] == 'stop' || $this->status[$player] == 'one') ? parent::ViewDices() : "";
        return $dices;
    }
}
