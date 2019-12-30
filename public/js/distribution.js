var date = new Date();  
var annee = date.getFullYear();
var date_selected;
var mode = 'toggle';
var options = {
    monthNames: ['Janvier','Février','Mars','Avril','Mai','Juin',
    'Juillet','Août','Septembre','Octobre','Novembre','Décembre'],
    monthNamesShort: ['Jan','Fév','Mar','Avr','Mai','Jun',
    'Jul','Aoû','Sep','Oct','Nov','Déc'],
    dayNames: ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'],
    dayNamesShort: ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'],
    dayNamesMin: ['Di','Lu','Ma','Me','Je','Ve','Sa'],
    dateFormat: 'yy-mm-dd',
    firstDay: 1,
    prevText: '&#x3c;Préc', prevStatus: 'Voir le mois précédent',
    prevJumpText: '&#x3c;&#x3c;', prevJumpStatus: 'Voir l\'année précédent',
    nextText: 'Suiv&#x3e;', nextStatus: 'Voir le mois suivant',
    nextJumpText: '&#x3e;&#x3e;', nextJumpStatus: 'Voir l\'année suivant',
    currentText: 'Courant', currentStatus: 'Voir le mois courant',
    todayText: 'Aujourd\'hui', todayStatus: 'Voir aujourd\'hui',
    clearText: 'Effacer', clearStatus: 'Effacer la date sélectionnée',
    closeText: 'Fermer', closeStatus: 'Fermer sans modifier',
    yearStatus: 'Voir une autre année', monthStatus: 'Voir un autre mois',
    weekText: 'Sm', weekStatus: 'Semaine de l\'année',
    dayStatus: '\'Choisir\' le DD d MM',
    defaultStatus: 'Choisir la date',
    isRTL: false
};

var optionsBig = $.extend({}, options, {
    numberOfMonths: [3, 4],
    minDate: new Date(annee, 0, 1),
    maxDate: new Date(annee+5, 11, 31),
    defaultDate: null,
    hideIfNoPrevNext: true,
    setDate: null,
    onSelect: onDateSelect,
    beforeShowDay: beforeShowDay,
    changeYear: true  
});

var optionsSmall = $.extend({}, options, {
  
});




  $(document).ready(function () {
    $("#datepicker").datepicker(optionsBig);
    $("#date_from").datepicker(optionsSmall);
    $("#date_to").datepicker(optionsSmall);
    $("#move-distri-step1").click(function() {
      moveDistri(1);
    });
    $("#move-distri-step2").click(function() {
      moveDistri(2);
    });
    $("#see-products").click(function() {
      showProducts();
    });
    $("#delete-distri").click(function() {
      toggleDistribution(date_selected);
    });
  });
  
  function moveDistri(step)
  {
    switch(step)
    {
      case 1:
        $("#nodelete-msg").modal('hide');
        $("#move-distri-msg").modal('show');
        break;
      case 2:
        mode = 'move';
        $("#move-distri-msg").modal('hide');
        break;
    }
  }
  
  function showProducts()
  {
    var urlShowProducts = root+'distribution/show_products/'+date_selected;
    $.ajax({
      url: urlShowProducts,
      dataType: 'json',
      beforeSend: function () 
      {
        showLoader(true);
      },
      success: function(data) 
      {
        $('#nodelete-msg').modal('hide');
        var html = '<h5>Liste des produits pour la distribution du '+date_selected.split('-').reverse().join('/')+'</h5>';
        html += '<ul class="list-group">';
        for (var i in data)
        {
          html += '<li class="list-group-item">'+data[i]+'</li>';
        }
        html += '</ul>';
        $("#msg-alert .modal-body").html(html);  
        $('#msg-alert').modal('show');
        showLoader(false);
      }
    });
  }
  
  function onDateSelect(dateText, inst)
  {    
    var dateStr = inst.selectedYear+'-'+twoDigits(inst.selectedMonth+1)+'-'+twoDigits(inst.selectedDay);
    switch(mode)
    {      
      case 'toggle':
        if (typeof distributions[dateStr] != 'undefined')//clic sur un jour de distribution 
        {
            var count = distributions[dateStr];
            date_selected = dateStr;
            if (count.nb_purchase > 0)//on peut juste déplacer
            {
                $('#nodelete-msg').modal('show');
            }
            else if (count.nb_product > 0)//on peut supprimer les produits 
            {
                $('#sup-product-msg').modal('show');
            }
            else//on supprime la distribution
            {
                toggleDistribution(dateStr);
            }
        }
        else//clic sur un jour sans distribution 
        {
          toggleDistribution(dateStr);
        }
        break;
      case 'move':
        if (typeof distributions[dateStr] != 'undefined')//clic sur un jour de distribution 
        {
          $("#msg-alert .modal-body").html('<p>Il y a déjà une distribution ce jour.</p>');        
          $('#move-alert').modal('show');
        }
        else//clic sur un jour sans distribution
        {
          var urlMoveDate = root+'distribution/move/'+date_selected+'/'+dateStr;
          $.ajax({
            url: urlMoveDate,
            beforeSend: function () 
            {
              showLoader(true);
            },
            success: function(msg) 
            {      
              if (msg == 'ok') 
              {                
                distributions[dateStr] = distributions[date_selected];
                delete distributions[date_selected];
                $("#datepicker").datepicker('refresh');
                $("#msg-alert .modal-body").html('<p>La distribution a été déplacée du '+date_selected.split('-').reverse().join('/')+' au '+dateStr.split('-').reverse().join('/')+'.</p>');        
                $("#msg-alert").modal('show');
              }
              else
                $("#msg-alert .modal-body").html('<p>Problème lors du déplacement de la distribution.</p>');  
              $('#move-alert').modal('show');
              showLoader(false);
            }
        });
        mode = 'toggle';
        break;
      }
    }
  }
  
  function toggleDistribution(dateStr)
  {
    var urlToggleDate = root+'distribution/toggle/'+dateStr;
    $.ajax({
      url: urlToggleDate,
      beforeSend: function () 
      {
        showLoader(true);
      },
      success: function(msg) 
      {
        if (msg=='active')
          distributions[dateStr] = {nb_product: 0, nb_purchase: 0};
        else
          delete distributions[dateStr];
        $("#datepicker").datepicker('refresh');
        showLoader(false);
        $('#sup-product-msg').modal('hide');
      }
    });
  }
  
  function beforeShowDay(date)
  {
    var dateStr = date.getFullYear()+'-'+twoDigits(date.getMonth()+1)+'-'+twoDigits(date.getDate());
    if (typeof distributions[dateStr] == 'undefined')
       return [true,'',''];  
    var count = distributions[dateStr];
    if (count.nb_product == 0 && count.nb_purchase == 0)
      return [true, "date-selected",dateStr];  
    if (count.nb_product > 0 && count.nb_purchase == 0)
      return [true, "date-selected-with-product",dateStr+" : "+count.nb_product+" produit"+(count.nb_product>1?"s":"")];  
    if (count.nb_product > 0 && count.nb_purchase > 0)
      return [true, "date-selected-with-purchase",dateStr+" : "+count.nb_product+" produit"+(count.nb_product>1?"s":"")+" / "+count.nb_purchase+" commande"+(count.nb_purchase>1?"s":"")];  
  }
  
  function twoDigits(num)
  {
    if (num<10)
      num = "0"+num;
    return num;
  }
  
  function showLoader(bool)
  {
    if (bool)
      $("#loader").show();
    else
      $("#loader").hide();
  }