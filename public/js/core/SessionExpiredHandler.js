(function () {
  var globalObject = window;
  var aia = globalObject.AIA = globalObject.AIA || {};
  var defaultRedirect = '/login';
  var defaultMessage = 'Tu sesión caducó. Guarda lo que puedas fuera de esta pantalla y vuelve a iniciar sesión.';
  var defaultDelayMs = 2500;
  var handledGlobally = false;

  /**
   * Único lugar donde se decide qué hacer ante un 401 de `SessionMiddleware` con
   * `sessionExpired: true`: anunciar el error y redirigir a `/login` (o a la ruta que
   * indique el propio payload). Los módulos que necesiten un mensaje distinto lo pasan
   * por `options.message` / `options.announce`; ninguno debe copiar este bloque.
   */
  function isSessionExpiredPayload(payload) {
    return !!(payload && payload.sessionExpired);
  }

  function defaultAnnounce(message) {
    if (typeof globalObject.toastr !== 'undefined' && globalObject.toastr.error) {
      globalObject.toastr.error(message);
      return;
    }

    console.error(message);
  }

  /**
   * @param {*} payload - el cuerpo JSON de una respuesta 401 (o cualquier valor; si no
   *   trae `sessionExpired: true` no hace nada y devuelve false).
   * @param {{message?: string, announce?: function, delayMs?: number, allowRepeat?: boolean}} [options]
   * @returns {boolean} true si el payload era una sesión caducada y se manejó.
   */
  function handle(payload, options) {
    if (!isSessionExpiredPayload(payload)) {
      return false;
    }

    options = options || {};

    if (handledGlobally && !options.allowRepeat) {
      return true;
    }

    handledGlobally = true;

    var announce = typeof options.announce === 'function' ? options.announce : defaultAnnounce;
    announce(options.message || defaultMessage);

    var redirectUrl = payload.redirect || defaultRedirect;
    var delayMs = typeof options.delayMs === 'number' ? options.delayMs : defaultDelayMs;

    globalObject.setTimeout(function () {
      globalObject.location.href = redirectUrl;
    }, delayMs);

    return true;
  }

  /**
   * Cubre todas las llamadas jQuery `$.ajax`/`$.getJSON` de la pantalla (dataType
   * 'json' manda `Accept: application/json`, que `SessionMiddleware` ya reconoce), sin
   * que cada módulo tenga que revisar su propio `.fail()`.
   *
   * @param {Function} jQuery - la instancia de jQuery de la pantalla ($).
   * @param {{message?: string, announce?: function, delayMs?: number}} [options]
   */
  function bindJQueryAjaxError(jQuery, options) {
    if (!jQuery || typeof jQuery !== 'function') {
      return;
    }

    jQuery(document).ajaxError(function (event, jqXHR) {
      if (!jqXHR || jqXHR.status !== 401) {
        return;
      }

      handle(jqXHR.responseJSON, options);
    });
  }

  /**
   * Atajo de una sola línea para el caso más común: enlazar jQuery con un
   * `announce` que reenvía a la función `showFeedback(type, message)` propia del
   * módulo. Existe para que los cuatro consumidores no repitan el mismo cierre.
   *
   * @param {Function} jQuery
   * @param {function(string, string): void} showFeedback
   */
  function bindWithShowFeedback(jQuery, showFeedback) {
    bindJQueryAjaxError(jQuery, {
      announce: function (message) {
        showFeedback('error', message);
      },
    });
  }

  /**
   * Cabeceras a añadir a un `fetch()` nativo para que `SessionMiddleware` reconozca la
   * petición como consumidora de JSON (el `fetch` nativo no manda `Accept:
   * application/json` por defecto).
   */
  function fetchHeaders() {
    return { 'X-AIA-Expect-Json': '1' };
  }

  /**
   * @param {Response} response
   * @param {object} [options]
   * @returns {Promise<boolean>} true si la respuesta era un 401 de sesión caducada
   *   (ya manejado); el llamador debe abortar su cadena `.then()` en ese caso.
   */
  function handleFetchResponse(response, options) {
    if (!response || response.status !== 401) {
      return Promise.resolve(false);
    }

    return response
      .clone()
      .json()
      .then(function (payload) {
        return handle(payload, options);
      })
      .catch(function () {
        return false;
      });
  }

  aia.SessionExpiredHandler = {
    handle: handle,
    bindJQueryAjaxError: bindJQueryAjaxError,
    bindWithShowFeedback: bindWithShowFeedback,
    fetchHeaders: fetchHeaders,
    handleFetchResponse: handleFetchResponse,
  };
})();
