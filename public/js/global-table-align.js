/**
 * Global Configuration for DataTables Alignment (Robust V3)
 * Uses standard DataTables API methods and modern ResizeObserver
 * to ensure alignment persists through dynamic layout changes.
 */
(function () {
  var checkInterval = 50;
  var maxAttempts = 200; // 10 seconds timeout
  var attempts = 0;

  function initFix($) {
    console.log('DataTables Alignment Fix: Initializing V3...');

    // 1. Override Defaults
    if ($.fn.dataTable.defaults) {
      $.extend(true, $.fn.dataTable.defaults, {
        autoWidth: false, // Essential for scrollY alignment
        scrollCollapse: true, // Allows table to shrink
        initComplete: function (settings, json) {
          var api = new $.fn.dataTable.Api(settings);
          // Use a small timeout to allow UI to settle
          setTimeout(function () {
            api.columns.adjust();
            attachObserver(settings);
          }, 200);
        },
      });
    }

    // Helper to attach ResizeObserver to a specific table's scroll body
    function attachObserver(settings) {
      if (!settings || !settings.nScrollBody) return;

      var scrollBody = settings.nScrollBody;
      if (window.ResizeObserver) {
        var ro = new ResizeObserver(function (entries) {
          // Start a micro-task to adjust columns
          // We check if table is still valid
          var api = new $.fn.dataTable.Api(settings);
          if (api.table().node()) {
            api.columns.adjust();
          }
        });
        ro.observe(scrollBody);
      }
    }

    // 2. Retroactive Fix for existing tables
    function retroactiveFix() {
      if ($.fn.dataTable.tables) {
        var tables = $.fn.dataTable.tables(true);
        $(tables).each(function () {
          var dt = $(this).DataTable();
          var settings = dt.settings()[0];
          if (settings) {
            try {
              if (settings.oFeatures) settings.oFeatures.bAutoWidth = false;
            } catch (e) {}

            attachObserver(settings);
            dt.columns.adjust().draw(false);
          }
        });
      }
    }

    // Bind to Window Resize (Backup)
    var resizeTimer;
    $(window).on('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        if ($.fn.dataTable.tables) {
          var tables = $.fn.dataTable.tables(true);
          $(tables).DataTable().columns.adjust();
        }
      }, 200);
    });

    // Bind to Window Load
    $(window).on('load', function () {
      retroactiveFix();
    });

    // Immediate trigger
    retroactiveFix();
  }

  function checkDeps() {
    if (document.getElementById('hot-container')) return;
    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable) {
      initFix(window.jQuery);
    } else if (attempts < maxAttempts) {
      attempts++;
      setTimeout(checkDeps, checkInterval);
    } else {
      console.warn('DataTables Alignment Fix: Timed out waiting for DataTables.');
    }
  }

  checkDeps();
})();
