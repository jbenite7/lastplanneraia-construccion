<?php

namespace App\Controllers\Core;

use App\Controllers\BaseController;
use App\Core\SessionMiddleware;
use App\Services\FeatureFlagService;

class FrontendConfigController extends BaseController
{
    public function javascript()
    {
        $flags = (new FeatureFlagService())->getPublicFrontendFlags();
        $flags['sessionTimeoutSeconds'] = SessionMiddleware::idleTimeoutSeconds();
        $payload = json_encode($flags, JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            $payload = '{}';
        }

        header('Content-Type: application/javascript; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo <<<JS
(function () {
  var flags = $payload;
  var noop = function () {};

  window.AIA = window.AIA || {};
  window.AIA.runtimeFlags = window.AIA.runtimeFlags || {};

  var runtimeFlags = window.AIA.runtimeFlags;
  runtimeFlags.consoleLogsEnabled = !!flags.consoleLogsEnabled;
  runtimeFlags.sessionTimeoutSeconds = Number(flags.sessionTimeoutSeconds || 0);

  var con = window.console = window.console || {};
  if (typeof con.error !== 'function') {
    con.error = noop;
  }
  if (typeof con.warn !== 'function') {
    con.warn = noop;
  }
  if (typeof con.info !== 'function') {
    con.info = noop;
  }
  if (typeof con.debug !== 'function') {
    con.debug = noop;
  }

  window.AIA.applyConsoleLogPolicy = function (enabled) {
    runtimeFlags.consoleLogsEnabled = !!enabled;

    if (!window.__AIA_ORIGINAL_CONSOLE_LOG__) {
      if (typeof con.log === 'function') {
        window.__AIA_ORIGINAL_CONSOLE_LOG__ = con.log.bind(con);
      } else {
        window.__AIA_ORIGINAL_CONSOLE_LOG__ = noop;
      }
    }

    con.log = runtimeFlags.consoleLogsEnabled ? window.__AIA_ORIGINAL_CONSOLE_LOG__ : noop;
  };

  window.AIA.applyConsoleLogPolicy(runtimeFlags.consoleLogsEnabled);
})();
JS;
        exit;
    }
}
