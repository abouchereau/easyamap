<?php

namespace App\Util;


class PasswordGenerator
{
  
  static public function make()
  {
    $pass = '';
    //un fruit ou légume au hasard
    $length = count(self::$listLegumes);
    $rand = rand(0,$length-1);
    $pass .= self::$listLegumes[$rand];
    //un caractère spécial
    $length = count(self::$listSpecialChar);
    $rand = rand(0,$length-1);
    //2 chiffres
    $pass .= self::$listSpecialChar[$rand];
    $pass .= rand(0,9);
    $pass .= rand(0,9);
    return $pass;
  }
  
  static protected $listSpecialChar = array("/","*","-","+");
  
  static protected $listLegumes = array(
    "Abricot",
    "Airelle",
    "Ananas",
    "Arbouse",
    "Avocat",
    "Banane",
    "Bigarade",
    "Brugnon",
    "Cabosse",
    "Cassis",
    "Ceriman",
    "Cerise",
    "Chayote",
    "Citron",
    "Coing",
    "Corme",
    "Curuba",
    "Datte",
    "Feijoas",
    "Figue",
    "Fraise",
    "Goyave",
    "Grenade",
    "Griotte",
    "Icaque",
    "Jambolan",
    "Jaque",
    "Jujube",
    "Kaki",
    "Kiwai",
    "Kiwi",
    "Kumquat",
    "Lime",
    "Litchi",
    "Longane",
    "Mangue",
    "Marang",
    "Marron",
    "Melon",
    "Mure",
    "Myrtille",
    "Nefle",
    "Noisette",
    "Noix",
    "Olive",
    "Orange",
    "Papaye",
    "Pasteque",
    "Peche",
    "Pistache",
    "Pitaya",
    "Poire",
    "Pomelo",
    "Pomme",
    "Prune",
    "Pruneau",
    "Prunelle",
    "Raisin",
    "Sapote",
    "Tamarin",
    "Tomate",
    "Yuzu",
    "Zevi",
    "Asperge",
    "Avocat",
    "Bette",
    "Blette",
    "Brocoli",
    "Carotte",
    "Celeri",
    "Choux",
    "Courge",
    "Echalote",
    "Endive",
    "Epinard",
    "Fenouil",
    "Giromon",
    "Haricot",
    "Igname",
    "Laitue",
    "Lentille",
    "Mache",
    "Navet",
    "Oignon",
    "Oseille",
    "Panais",
    "Patate",
    "Patisson",
    "Poireau",
    "Poivron",
    "Potiron",
    "Radis",
    "Rhubarbe",
    "Rutabaga",
    "Salade",
    "Salsifi",
    "Tomate");
}