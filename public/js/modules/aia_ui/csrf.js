// Token CSRF de la página: lo emite la vista en <meta name="csrf-token">.
(function () {
  "use strict";
  window.aiaCsrfToken = function () {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return (meta && meta.getAttribute("content")) || "";
  };
})();
