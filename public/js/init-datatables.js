var dataTable = null;
$(document).ready(function() {
    dataTable = $(".sorttable").on('init.dt', function () {
        $("#DataTables_Table_0_filter input").attr('placeholder','Rechercher');
    }).on('order.dt', function () {
        $('body').trigger('table-ordered');
    }).DataTable({
        dom: 'pftpil',
        pageLength: 50,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, "Tout"]],
        order: [],
        language: {
        "sProcessing":     "Traitement en cours...",
        "sSearch":         "",
        "sLengthMenu":     "_MENU_ &eacute;l&eacute;ments par page",
        "sInfo":           "_START_ &#9654; _END_ (total: _TOTAL_)",
        "sInfoEmpty":      "",
        "sInfoFiltered":   "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
        "sInfoPostFix":    "",
        "sLoadingRecords": "Chargement en cours...",
        "sZeroRecords":    "Aucun &eacute;l&eacute;ment &agrave; afficher",
        "sEmptyTable":     "Aucune donn&eacute;e disponible dans le tableau",
        "oPaginate": {
            "sFirst":      "Premier",
            "sPrevious":   "Pr&eacute;c&eacute;dent",
            "sNext":       "Suivant",
            "sLast":       "Dernier"
        },
        "oAria": {
            "sSortAscending":  ": activer pour trier la colonne par ordre croissant",
            "sSortDescending": ": activer pour trier la colonne par ordre d&eacute;croissant"
        }
        }
});
}); 