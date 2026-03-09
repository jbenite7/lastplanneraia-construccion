/**
 * Mobile Table Fix - Card View Injector
 * Auto-injects data-label attributes for CSS Card View
 */
document.addEventListener('DOMContentLoaded', function () {
  initMobileTables();

  // Bind DataTables redraw event only if jQuery is available
  if (typeof $ !== 'undefined' && $.fn && $.fn.dataTable) {
    $(document).on('draw.dt', function () {
      initMobileTables();
    });
  }
});

function initMobileTables() {
  const tables = document.querySelectorAll('table:not(#hot-container table)');

  tables.forEach((table) => {
    // Find headers
    const headers = Array.from(table.querySelectorAll('thead th')).map((th) => th.innerText.trim());

    // Find rows
    const rows = table.querySelectorAll('tbody tr');

    rows.forEach((row) => {
      const cells = row.querySelectorAll('td');
      cells.forEach((cell, index) => {
        if (headers[index]) {
          cell.setAttribute('data-label', headers[index]);
        }
      });
    });
  });
  console.log('Mobile Table Fix applied: Data-labels injected.');
}
