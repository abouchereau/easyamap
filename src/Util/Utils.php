<?php

namespace App\Util;

class Utils 
{
  /** 
   * renvoie le nombre de dates par mois à partir d'une liste de dates de la forme yyyy-mm-dd
   * @param type $dates
   * @return int
   */
  
  static public function getNbPerMonth($dates)
  {
    $nb_per_month = array();
    foreach ($dates as $date)
    {
      $mois = substr($date,0,7);
      if (!isset($nb_per_month[$mois]))
        $nb_per_month[$mois] = 0;
      $nb_per_month[$mois]++;
    }
    return $nb_per_month;
  }
  
  static public function numerize($num) {
      return 1.0*str_replace(',','.',$num);
  }
}
