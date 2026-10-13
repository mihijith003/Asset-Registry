import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// NEW: DataTables (search, sortable columns and paging) for every <table data-datatable>.
import DataTable from 'datatables.net-dt';
import 'datatables.net-dt/css/dataTables.dataTables.css';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('table[data-datatable]').forEach((table) => {
        new DataTable(table, {
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            // Keep the order the server sent (newest first) until a header is clicked.
            order: [],
            columnDefs: [
                // Columns whose <th> has class "no-sort" (e.g. the Print button) are not sortable/searchable.
                { targets: 'no-sort', orderable: false, searchable: false },
            ],
            language: {
                search: 'Search:',
                emptyTable: table.dataset.empty || 'No records found.',
            },
        });
    });
});
