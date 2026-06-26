$(document).ready(function() {
  $('[data-toggle="tooltip"]').tooltip({
    container : 'body'
  });
  if (typeof $('').select2 == 'function') {
    $('select:not(#nb,.input-sm,.sonata-filter-option,.not-select2)').select2();
  }
});

const toClipboard = (id) => {
    const elt = document.getElementById(id);
    elt.select();
    const text = elt.value.replaceAll(" ","");
    navigator.clipboard.writeText(text)
        .then(()=>alert("Texte copié dans le presse-papier : \n"+text))
        .catch(console.error);
}

const validatePayment = (idPayment, checked,) => {    
    const url = root+'ajax/checkVirement';
    $.ajax({
        url: url,
        type: 'POST',
        data: { 
            'idPayment': idPayment, 
            'checked': (checked?"1":"0"),
            'currentFarm': getCurrentFarm()
        },
        beforeSend: function () {    
                $("#loader").show();
        },
        success: function(data) {                
            $("#loader").hide();
           /* if (checked) {
                alert("Nous avons enregistré l'émission de votre virement.\nNous allons avertir le producteur afin qu'il puisse valider la réception.");
            }*/
            window.location.reload();
        }
    });

    
};