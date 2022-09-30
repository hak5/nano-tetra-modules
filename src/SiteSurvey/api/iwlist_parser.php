<?php namespace pineapple;

class iwlist_parser
{
    public function iwlist_parser()
    {
    }

    public function parseString($string)
    {
        if (empty($string)) {
            return false;
        }

        // Rueckgabe dieser Funktion:
        // $array[device][Cell#][Cell-Feld] = Wert
        // z.b. $array[eth0][1][ESSID] = "MGees_WLan"

        $ergebnis = array();

        $string = explode("\n", $string);

        $device = $cell = "";

        foreach ($string as $zeile) {
            if (substr($zeile, 0, 1) != ' ') {
                $device = substr($zeile, 0, strpos($zeile, ' '));
                $ergebnis[$device] = array();
            } else {
                $zeile = trim($zeile);
                // Zeile kann sein:
                // Cell ## - Address: Wert
                // Feld:Wert
                // Quality=bla Signal Level=bla2
                if (substr($zeile, 0, 5) == 'Cell ') {
                    $cell = (int)substr($zeile, 5, 2);
                    $ergebnis[$device][$cell] = array();
                    $doppelp_pos = strpos($zeile, ':');
                    $ergebnis[$device][$cell]['Address'] =
                        trim(substr($zeile, $doppelp_pos + 1));
                } elseif (substr($zeile, 0, 8) == 'Quality=') {
                    $first_eq_pos = strpos($zeile, '=');
                    $last_eq_pos = strrpos($zeile, '=');
                    $slash_pos = strpos($zeile, '/') - $first_eq_pos;
                    //$ergebnis[$device][$cell]['Quality'] = trim(substr($zeile, $first_eq_pos + 1, $slash_pos - 1));
                    $ergebnis[$device][$cell]['Quality'] = trim(number_format(substr($zeile, $first_eq_pos + 1, $slash_pos - 1) * 100 / 70, 0));
                    $ergebnis[$device][$cell]['Signal level'] = trim(substr($zeile, $last_eq_pos + 1));
                } else {
                    $doppelp_pos = strpos($zeile, ':');
                    $feld = trim(substr($zeile, 0, $doppelp_pos));
                    if (!empty($ergebnis[$device][$cell][$feld])) {
                        $ergebnis[$device][$cell][$feld] .= "\n";
                    }
                    // Leer- und "-Zeichen rausschmeissen - ESSID steht immer in ""
                    @$ergebnis[$device][$cell][$feld] .= trim(str_replace('"', '', substr($zeile, $doppelp_pos + 1)));
                }
            }
        }
        return $ergebnis;
    }

    public function parseScanAll()
    {
        return $this -> parseString(shell_exec("iwlist scan 2> /dev/null"));
    }

    public function parseScanDev($device)
    {
        return $this -> parseString(shell_exec("iwlist ".$device." scan 2> /dev/null"));
    }
}
