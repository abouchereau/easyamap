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

  static public function htmlToText($html) {
    // Remplacer certains blocs par des retours ligne
    $html = preg_replace('/<(br|\/p|\/div)>/i', "\n", $html);

    // Supprimer les balises
    $text = strip_tags($html);

    // Décoder les entités HTML (&nbsp; etc.)
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Nettoyage espaces
    $text = preg_replace("/[ \t]+/", " ", $text);
    $text = preg_replace("/\n\s*\n/", "\n\n", $text);

    return trim($text);
}
}
