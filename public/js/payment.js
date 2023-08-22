
var id_payment = null;
var tmp_received = {
    $description_td: null,
    $td: null,
    amount: 0,
    received: 0
};

function refreshFilters() {
    var url = "";
    if (isReferentPage) {
        url = root+'paiements_referent/{page}/{contract}/{farm}/{received}/{adherent}';
        url = url.replace('{adherent}',$("#filter-adherent").val());
    }
    else {
        url = root+'paiements_adherent/{page}/{contract}/{farm}/{received}';
    }    
    url = url.replace('{contract}',$("#filter-contract").val());
    url = url.replace('{farm}',$("#filter-farm").val());
    url = url.replace('{received}',$("#filter-received").val());
    url = url.replace('{page}',"1");
    window.location.href = url;
}

$(document).ready(function() {
//       $('.sonata-filter-option').val(1);
       $('select.form-control').change(function() {
           refreshFilters();
           //$(this).closest('form').trigger('submit');
       });
//       if ($("#filter_fkFarm_value").children().length <3)
//       {
//           $("#filter_fkFarm_value").parent().hide();
//       }
       
       $('.paiement .row-received').each(function () {
           var received = $.trim($(this).html());
           var receivedAt = $.trim($(this).parent().find('.row-receivedAt').html().replace(/&nbsp;/g, ''));
           var amount = $.trim($(this).parent().find('.row-amount').html());
           if (receivedAt == '') {
               $(this).addClass('text-danger');
           } 
           else {
               if (amount == received)
                    $(this).addClass('text-success');
                else
                    $(this).addClass('text-warning');
           }
       })
       
       //page historique des paiements
      /* $('.paiement .row-fkUser, .paiement .row-fkFarm').attr('rel','tooltip').attr('data-container','body').attr('data-placement','bottom');
       $('.paiement .row-fkUser, .paiement .row-fkFarm').attr('data-title','Historique des paiements');
       $('.paiement .row-fkUser, .paiement .row-fkFarm').click(function () {
                var id_payment = $(this).attr('objectid');
                window.location.href = root+'payment_history_from_one_payment/'+id_payment;
            });
       */
       if (isReferentPage)
       {
            $('.paiement .row-description, .paiement .row-amount, .paiement .row-received').attr('rel','tooltip').attr('data-container','body').attr('data-placement','bottom');
            $('.paiement .row-amount').attr('data-title','Modifier le montant');
            $('.paiement .row-received').each(function () {
                if (toNum($(this).html()) == 0)
                    $(this).attr('data-title','Accuser réception du paiement');
                else
                    $(this).attr('data-title','Modifier le montant du paiement reçu');

            });
            
            $('.paiement .row-amount').click(function () {
                console.log("YO",$(this).attr('objectid'));
                window.id_payment = $(this).attr('objectid');
                var amount = toNum($(this).html());
                var user = $.trim($(this).parent().find('.row-fkUser').html());
                $("#amount").val(number_format(amount,2,',',' '));
                $(".adherent").html(user);
                $("#modal-amount").modal('show');
            });
            
            $('.paiement .row-received').click(function () {
                window.id_payment = $(this).attr('objectid');
                var received = toNum($(this).html());
                if (received == 0)
                    received = toNum($(this).parent().find('.row-amount').html());
                tmp_received.$td = $(this);
                tmp_received.$description_td = $(this).parent().find('td.row-description');
                tmp_received.amount = toNum($(this).parent().find('.row-amount').html());
                var user = $.trim($(this).parent().find('.row-fkUser').html());
                $("#received").val(number_format(received,2,',',' '));
                if (typeof descriptions[window.id_payment] != 'undefined') {
                    $('#split-payment').html(generateSplitForm(descriptions[window.id_payment]));
                    $("#modal-split").show();
                }
                else {
                    $('#split-payment').html('');
                    $("#modal-split").hide();
                }
                     
                $("#split-payment").html();
                $(".adherent").html(user);
                $("#modal-received").modal('show');
            });
            
        }
        $("[rel='tooltip']").tooltip();
});

function changePaymentDescription() {
    var descr = $("#description").val();
    var url = root+'payment_description';

    var data = {id_payment: window.id_payment, description: descr};
    $.ajax({
        url: url,
        method: "POST",
        data: data,
        beforeSend: function () 
        {
            $("#modal-description").modal('hide');
            $('#loading').modal('show');
            $('.btn').prop( "disabled", true );
        },
        success: function(msg) 
        {
            if (msg == 'ok')
                window.location.reload();
            else
                alert(msg);
        }
    });
}

function changePaymentAmount() {
    var amount = $("#amount").val();    
    var url = root+'payment_amount';
    var data = {id_payment: window.id_payment, amount: amount};
    $.ajax({
        url: url,
        method: "POST",
        data: data,
        beforeSend: function () 
        {
            $("#modal-amount").modal('hide');
            $('#loading').modal('show');
            $('.btn').prop( "disabled", true );
        },
        success: function(msg) 
        {            
            if (msg == 'ok')
                window.location.reload();          
            else
                alert(msg);
        }
    });
}


function changePaymentReceived() {
    var received = toNum($("#received").val());
    var split_index = -1;
    if ($('input:radio[name=split]:checked').length > 0)
            split_index = $('input:radio[name=split]:checked').val()*1;
    else {
        alert("Aucun choix n'est sélectionné.");
        return false;
    }
    var split = [];
    $('input[name^=split'+split_index+']').each(function() {
        split.push(toNum($(this).val()));
    });
    var url = root+'payment_received';
    var data = {id_payment: window.id_payment, received: received, split_index: split_index, split: split};
    tmp_received.received = received;
    $.ajax({
        url: url,
        method: "POST",
        data: data,
        beforeSend: function () 
        {
            $("#modal-received").modal('hide');
            $('#loading').modal('show');
            $('.btn').prop( "disabled", true );
        },
        success: function(msg) 
        {
            $('#loading').modal('hide');
            if (msg.indexOf('[') == 0) {
                if (tmp_received.received == 0) {
                    tmp_received.$td.removeClass('text-warning').removeClass('text-success').addClass('text-danger');
                    tmp_received.$td.html(toCurrency(0));
                }   
                else if (tmp_received.received == tmp_received.amount) {
                    tmp_received.$td.removeClass('text-warning').removeClass('text-danger').addClass('text-success');
                    tmp_received.$td.html(toCurrency(tmp_received.received));
                }
                else {
                    tmp_received.$td.removeClass('text-success').removeClass('text-danger').addClass('text-warning');
                    tmp_received.$td.html(toCurrency(tmp_received.received));
                }
                tmp_received.$td.addClass("success");
                
                tmp_received.$description_td.find('span.glyphicon-check').removeClass('glyphicon-check').addClass('glyphicon-unchecked');
                tmp_received.$description_td.find('dt').eq(split_index).find('span.glyphicon-unchecked').removeClass('glyphicon-unchecked').addClass('glyphicon-check');
                var split_txt = [];
                for (var i in split) {
                    split_txt.push(toCurrency(split[i]));
                }
                tmp_received.$description_td.find('dd').eq(split_index).find('.repartition').html(split_txt.join(' / ')+' ');
                window.descriptions[window.id_payment] = JSON.parse(msg);
            }
            else {
                $("#msg-alert .modal-body").html(msg);
                $("#msg-alert").modal('show');
            }
            $('.btn').prop( "disabled", false );
        }
    });
}


function number_format(number, decimals, dec_point, thousands_sep) {
  number = (number + '')
    .replace(/[^0-9+\-Ee.]/g, '');
  var n = !isFinite(+number) ? 0 : +number,
    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
    s = '',
    toFixedFix = function(n, prec) {
      var k = Math.pow(10, prec);
      return '' + (Math.round(n * k) / k)
        .toFixed(prec);
    };
  // Fix for IE parseFloat(0.55).toFixed(0) = 0;
  s = (prec ? toFixedFix(n, prec) : '' + Math.round(n))
    .split('.');
  if (s[0].length > 3) {
    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
  }
  if ((s[1] || '')
    .length < prec) {
    s[1] = s[1] || '';
    s[1] += new Array(prec - s[1].length + 1)
      .join('0');
  }
  return s.join(dec);
}

function toNum(txt) {
    return 1.0*$.trim(txt).replace(',','.').replace(' ','').replace('€','');
}

function toCurrency(num) {
    return number_format(num,2,',',' ')+" €";
}

function generateSplitForm(tab) {
    var html = '';
    var payments = tab[1];
    var chosen_payment = tab[3];    
    for (var i in payments) {        
        var choices = payments[i];
        html += '<label><input type="radio" name="split" value="'+i+'"';
        if (chosen_payment[i] || payments.length==1) {
            html += ' checked="checked"';
        }
        html += ' />';
        html += ' <b>'+choices.length+'</b> paiement'+(choices.length>1?'s':'')+' </label> ';
        for (var j in choices) {
            var choice = choices[j];
            html += '<input type="text" value="'+number_format(choice[1],2,',',' ')+'" class="form-control mini-input" name="split'+i+'['+j+']"  />';
            if (j < choices.length-1) {
                html += " ";
            }
        }
        html += '<br />';        
    }   
    return html;
}
   