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
    const url = root+'ajax/checkIssued';
    $.ajax({
        url: url,
        type: 'POST',
        data: {'idPayment': idPayment},
        beforeSend: function () {    
            showSpinner();
        },
        success: function(data) {                
            hideSpinner();
            window.location.reload();
        }
    });    
};

(async () => {
    if ('serviceWorker' in navigator && window.location.href.indexOf("localhost") == -1) {
        navigator.serviceWorker.register('service-worker-v1.2.js');
    }
})();

