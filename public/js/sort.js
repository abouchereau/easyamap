$(document).ready(function() {
 $('.sortable').sortable({
    helper: 'clone',//évite le onclick
    update: function (event, ui) {
        var $item = $(this).data().uiSortable.currentItem;
        var id_from = $item.attr('id');
        var id_before = $item.prev().attr('id');
        if (id_before == id_from) {
            id_before = 0;
        }
        var id_after = $item.next().attr('id');
        if (id_after == id_from) {
            id_after = 0;
        }
        var url = window.baseUrl.replace('__FROM__',id_from).replace('__BEFORE__',id_before).replace('__AFTER__',id_after);
        $.ajax({
            type: 'POST',
            url: url,
            beforeSend: function () {
                $("#loading").modal('show');
            },
            error: function () {
                 $("#loading").modal('hide');
                 alert('Erreur lors du changement d\'ordre');
            },
            success: function (msg) {                
                 $("#loading").modal('hide');
                 if (msg!=':)') {
                     alert('Erreur lors du changement d\'ordre');
                 }
                
            }
        });
    }
});
$('body').on('table-ordered', function () {
    $('.sortable').sortable( "disable" );
});
});