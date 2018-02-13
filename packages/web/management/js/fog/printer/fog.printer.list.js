(function($) {
    var deleteSelected = $('#deleteSelected');
    var deleteModal = $('#deleteModal');
    var passwordField = $('#deletePassword');
    var confirmDelete = $('#confirmDeleteModal');
    var cancelDelete = $('#closeDeleteModal');

    var numPrinterString = confirmDelete.val();

    function disableButtons(disable) {
        deleteSelected.prop('disabled', disable);
    }
    function onSelect(selected) {
        var disabled = selected.count() == 0;
        disableButtons(disabled);
    }

    disableButtons(true);
    var table = Common.registerTable($('#dataTable'), onSelect, {
        order: [
            [0, 'asc']
        ],
        columns: [
            {data: 'name'},
            {data: 'config'},
            {data: 'model'},
            {data: 'port'},
            {data: 'file'},
            {data: 'ip'},
            {data: 'configFile'}
        ],
        rowId: 'id',
        columnDefs: [
            {
                responsivePriority: -1,
                render: function(data, type, row) {
                    return '<a href="../management/index.php?node='+Common.node+'&sub=edit&id=' + row.id + '">' + data + '</a>';
                },
                targets: 0
            },
            {
                responsivePriority: 0,
                render: function(data, type, row) {
                    return row.config == 'Local' ? 'TCP/IP' : data;
                },
                targets: 1
            }
        ],
        processing: true,
        serverSide: true,
        ajax: {
            url: '../management/index.php?node='+Common.node+'&sub=list',
            type: 'POST'
        }
    });

    if (Common.search && Common.search.length > 0) {
        table.search(Common.search).draw();
    }

    deleteSelected.click(function() {
        disableButtons(true);
        confirmDelete.val(numPrinterString.format(''));
        Common.massDelete(null, function(err) {
            if (err.status == 401) {
                deleteModal.modal('show');
            } else {
                onSelect(table.rows({selected: true}));
            }
        }, table);
    });
})(jQuery);