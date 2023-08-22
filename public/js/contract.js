var mois = ['Janvier','Février','Mars','Avril','Mai','Juin',
    'Juillet','Août','Septembre','Octobre','Novembre','Décembre'];  
var jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
var options1 = {
    monthNames: mois,
    monthNamesShort: ['Jan','Fév','Mar','Avr','Mai','Jun',
    'Jul','Aoû','Sep','Oct','Nov','Déc'],
    dayNames: jours,
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
    isRTL: false,
    onSelect: function () 
    {
      displayDistributionsBetween($("#contract_periodStart").val(), $("#contract_periodEnd").val());
    }
};  
var options2 = $.extend({}, options1);
options2.onSelect = function () {};
  //ajout du nom des fermes
var products = [];
$(document).ready(function () {
  $('.entity').css({'border':'1px solid #CCC', 'padding':'10px','background-color':'#FAFAFA'});
  $('.entity .custom-checkbox').each(function () {
    var farm = $(this).find('.farm-checkbox').html();
    if (typeof products[farm] == 'undefined')
      products[farm] = 0;
    products[farm]++;      
  });
  
  var iterate = 0;
  var next = 0
  $('.entity .custom-checkbox').each(function () {
    if (iterate == next)
    {      
      var farm = $(this).find('.farm-checkbox').html();      
      $(this).before('<div class="page-header">'+farm+'</div>');
      next = products[farm];
      iterate = 0;
    }   
    iterate++;
  });
  
  $('.page-header').click(function () {
    var farm1 = $(this).html();
    var all_checked = true;
    
    $('.entity .custom-checkbox').each(function () {
      var farm2 = $(this).find('.farm-checkbox').html(); 
      if (farm1 == farm2 && !$(this).find('input[type=checkbox]').prop('checked'))
      {
        all_checked = false;
        return false;
      }      
    });
    
    $('.entity .custom-checkbox').each(function () {
      var farm2 = $(this).find('.farm-checkbox').html(); 
      if (farm1 == farm2)
      {
        $(this).find('input[type=checkbox]').prop('checked', !all_checked);        
      }      
    });
    
  });
  
  $('.entity label:first-child').after('<br /><button class="btn btn-xs btn-info" onclick="checkAll();return false;">Tout cocher / décocher</a>');
  
  $("#contract_periodStart, #contract_periodEnd").datepicker(options1);
  $("#contract_fillDateEnd, #contract_fillDateStart, #contract_countPurchaseSince, #contract_dateTest").datepicker(options2);
  
  //on disable les produits ayant déjà eu des commandes
  $('.entity .custom-checkbox').each(function () {
        var $checkbox = $(this).find('input[type=checkbox]');
        if (typeof product_purchased[$checkbox.val()] != 'undefined') {
            $checkbox.addClass('checkbox-disabled');
            $checkbox.click(function () {return false;})
            $(this).attr('title',"Il n'est pas possible de retirer ce produit car il fait déjà l'objet d'une ou plusieurs commandes")
        }
    });
    
    //on empeche de changer la date si des commandes sont déjà passées
    if (!can_be_deleted) {
        $("#contract_periodStart, #contract_periodEnd").attr("disabled", true);
    }
  
});


function checkAll()
{
    var all_checked = true;
    $('.entity .custom-checkbox').each(function () {
      if (!$(this).find('input[type=checkbox]').prop('checked'))
      {
        all_checked = false;
        return false;
      }      
    });
    
    $('.entity .custom-checkbox').each(function () {
        $(this).find('input[type=checkbox]').prop('checked', !all_checked);
    });
}

function displayDistributionsBetween(dateStart, dateEnd)
{

  if (dateStart != '' && dateEnd != '')
  {
    var url = root+'distributions_between/'+dateStart+'/'+dateEnd;
    $.ajax({
      url: url,
      dataType: 'json',
      beforeSend: function () 
      {

      },
      success: function(data) 
      {
        
        var nb = data.length;
        var html = nb+' distribution(s) entre ces 2 dates :<ul class="list-group">';
        for (var i in data)
        {
          var date = new Date(data[i]);
          html += '<li class="list-group-item">'+jours[date.getDay()]+' '+date.getDate()+' '+mois[date.getMonth()]+'</li>';
        }
        html += '</ul>';
        $("#msg-alert .modal-body").html(html);
        $("#msg-alert").modal('show');
      }
    });
  }
}