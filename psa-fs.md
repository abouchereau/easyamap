

## Remplit la première ligne de PSA-FS

 
Copier-coller le code ci-dessous dans un marque-page de votre navigateur (champ URL)

`javascript:heures=["0","7,40","7,40","7,40","7,40","7,40","0"];a=document.querySelector("iframe").contentDocument;heures.forEach((v,i)=>{let b=a.getElementById("TIME"+(1+i)+"$0");b.value=v;b.dispatchEvent(new Event('change'));});void 0`

Il faut ajuster la variable _heures_ selon les valeurs que vous souhaitez.

(TODO : faire évoluer pour que ça fonctionne sur plusieurs lignes de projet) 


## Remplit la modale de PSA-FS


Copier-coller le code ci-dessous dans un marque-page de votre navigateur (champ URL)

`javascript:localisations=[3,5,7,7,5,5,3];dureeDej=2;a=document.querySelector("iframe").contentDocument;for(let i=1;i<8;i++){for(let j=0;j<3;j++){let b=a.getElementById("UC_DAILYREST"+i+"$"+j);b.selectedIndex=[1,7].includes(i)?1:3;b.dispatchEvent(new Event('change'));}let b=a.getElementById("UC_TIME_LIN_WRK_UC_DAILYREST1"+i+"$0");b.value=[1,7].includes(i)?0:dureeDej;b.dispatchEvent(new Event('change'));for(let j=0;j<2;j++){let b=a.getElementById("UC_LOCATION_A"+i+"$"+j);b.selectedIndex=localisations[i-1];b.dispatchEvent(new Event('change'));}};void 0`

Ajuster la variable _localisations_ et _dureeDej_ selon les valeurs que vous souhaitez.

Valeurs possibles pour localisations (une par jour):
<1>: CGI
<2>: Exceptionel TLT
* 3: N/A
* 4: Ponctuel TLT
* 5: Regulier TLT
* 6: Site client
* 7: Vélo Site CGI
* 8: Vélo Site client

Explications sur les bookmarklets : https://www.freecodecamp.org/news/what-are-bookmarklets/
