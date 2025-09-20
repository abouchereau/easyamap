var has_unsaved_changes = false;

const sepaData = `BCD
001
1
SCT

__BENEFICIAIRE__
__IBAN__
EUR__MONTANT__
__REFERENCE__`;

$(document).ready(function () {
    
    $('body').on('has-changes', function () {
        window.has_unsaved_changes = true;
        $('button.btn-primary').removeAttr("disabled");
    });
    
    $('.spinner').on('input',function() {
      $('body').trigger('has-changes');
	});
    
    //controle des input number
    $("input[type=number]").bind('keyup mouseup',function() {
        checkNumValue($(this));
        checkMinMax($(this));
        if ($(this).hasClass('subscription')) {
            dispatchValueOnLine($(this));
        }
    });
        
    //mise en valeur de la lign et de la colone survolée.
    $("td.bg-grey").hover(function () {
        if ($(this).find('input[type=number]').length > 0) {
            //horizontal
            $(this).parent().find('td:nth-child(1), td:nth-child(2)').addClass('td-highlight');
            //vertical
            var col_num = $(this).index()-2;//-2 pour un colspan
            $(this).parent().parent().find('tr.tr-date').find('td:nth-child('+col_num+')').addClass('td-highlight');
        }        
    },function () {
        if ($(this).find('input[type=number]').length > 0) {
            //horizontal
            $(this).parent().find('td:nth-child(1), td:nth-child(2)').removeClass('td-highlight');
            //vertical
            var col_num = $(this).index()-2;//-2 pour un colspan
            $(this).parent().parent().find('tr.tr-date').find('td:nth-child('+col_num+')').removeClass('td-highlight');
        }
    });
    
    $('.plus, .minus').hover(function () {
        $(this).parent().parent().find('td:nth-child(1), td:nth-child(2)').addClass('td-highlight');
    }, function () {
        $(this).parent().parent().find('td:nth-child(1), td:nth-child(2)').removeClass('td-highlight');        
    });
    
    $('.plus, .minus').click(function () {
        $('body').trigger('has-changes');
        var increm = ($(this).hasClass('plus')?1:-1);
        $(this).parent().parent().find('input[type=number]').each(function () {
            if (!$(this).is(':disabled')) {
                $(this).val(1.0*$(this).val()+increm);
                checkMinMax($(this));
            }
        });
        return false;
    });
    if($('#payment_received_modal').length>0) {
        $('#payment_received_modal').modal('show');
    }

    $("#virement-btn").click(e=> {
        e.preventDefault(); 
        $("#virement").modal("show");
        let virement = {
            "iban": "FR01 1234 1234 1234 1234 1234 123",
            "montant": "27,00",
            "reference": "EASYAMAP-CHAMBRAY-00001",
            "beneficiaire": "Roussel"
        };
        $("#virement-iban").val(virement.iban);
        $("#virement-montant").val(virement.montant);
        $("#virement-reference").val(virement.reference);

        let qrStr = sepaData
            .replace("__BENEFICIAIRE__", virement.beneficiaire)
            .replace("__IBAN__", virement.iban)
            .replace("__MONTANT__", virement.montant.replace(",","."))
            .replace("__REFERENCE__", virement.reference);

        const qr = qrcode(0, 'M'); // niveau de correction
        qr.addData(qrStr);
        qr.make();

       $("#qr-code").html(qr.createImgTag(3, 10, "QR Code"));

        return false;
    });

});

window.onbeforeunload = function () {
  if (window.has_unsaved_changes)
    return "Des modifications n'ont pas été enregistrées.";
};

$("button.btn").click(function () {
  window.has_unsaved_changes = false;
  $('#loading').modal('show');
  var notEmptyValues = {};
  $('input.spinner').each(function() {
    if ($(this).val() != '' && $(this).val() != '0')
      notEmptyValues[$(this).attr('name')] = $(this).val()*1;
  });
  $("#json").val(JSON.stringify(notEmptyValues));
  $("#current_farm").val(getCurrentFarm());
  $("#json_form").submit();
});

function getCurrentFarm() {
    if ($("#amap-tabs").find('li.active').length > 0) {
        return $("#amap-tabs").find('li.active').find('a').attr('href').replace('#farm','');
    }
    return 0;    
}

function checkNumValue($input) {
    //suppression des décimales
    if ($input.val().indexOf('.') > -1) {
       $input.val($input.val().substr(0,$input.val().indexOf('.')));
    }

    if ($input.val().indexOf(',') > -1) {
       $input.val($input.val().substr(0,$input.val().indexOf(',')));
    }
    //suppression des caractères non numériques
    $input.val($input.val().replace(/[^0-9]+/g, ''));
}

function checkMinMax($input) {
    if ($input.val() != '') {
        var max = $input.attr('max');
        if (typeof max !== typeof undefined && max !== false) {
            if ($input.val() > max) {
                $input.val(max);
            }
        }        
        var min = $input.attr('min');
        if (typeof min !== typeof undefined && min !== false) {
            if ($input.val() < min) {
               $input.val(min);
            }
        }
    }   
}

function dispatchValueOnLine($input) {
    var val = $input.val();
    $input.parent().parent().find('input').each(function() {
        $(this).val(val);
        checkNumValue($(this));
        checkMinMax($(this));
    })
}

//TODO : faire ça mieux
function switchToUser(id_user) {
    var url_split = window.location.href.split('/');
    url_split[url_split.length-1] = id_user;
    $('#loading2').modal('show');
    window.location.href = url_split.join('/');
}