(function () {
  var globalObject = window;
  var aia = globalObject.AIA = globalObject.AIA || {};
  var runtimeFlags = aia.runtimeFlags || {};
  var timeoutSeconds = Number(runtimeFlags.sessionTimeoutSeconds || 0);
  var currentPath = normalizePath(globalObject.location.pathname || '/');
  var publicPaths = {
    '/': true,
    '/login': true,
    '/password/update': true,
  };
  var timeoutMs = timeoutSeconds * 1000;
  var lastActivityKey = 'aia.session.last-activity-at';
  var logoutKey = 'aia.session.logout-at';
  var touchUrl = '/session/touch';
  var defaultLogoutUrl = '/logout?timeout=1';
  var activityThrottleMs = 1000;
  var touchIntervalMs = 60000;
  var expiryTimerId = null;
  var redirecting = false;
  var lastInteractionAt = 0;
  var lastTouchAt = Date.now();
  var lastActivityAt = Date.now();

  if (!timeoutSeconds || timeoutSeconds <= 0 || publicPaths[currentPath]) {
    return;
  }

  if (typeof globalObject.localStorage !== 'undefined') {
    var storedActivityAt = parseTimestamp(globalObject.localStorage.getItem(lastActivityKey));

    if (storedActivityAt && Date.now() - storedActivityAt <= timeoutMs) {
      lastActivityAt = storedActivityAt;
    }
  }

  recordActivity({ touchServer: false, force: true });
  bindActivityEvents();
  bindLifecycleEvents();
  scheduleExpiryCheck();

  aia.SessionTimeoutManager = {
    forceLogout: function (redirectUrl) {
      triggerLogout(redirectUrl || defaultLogoutUrl, true);
    },
    recordActivity: function () {
      recordActivity({ touchServer: true });
    },
  };

  function normalizePath(pathname) {
    if (!pathname || pathname === '/') {
      return '/';
    }

    return pathname.charAt(pathname.length - 1) === '/'
      ? pathname.slice(0, -1)
      : pathname;
  }

  function parseTimestamp(value) {
    var parsed = Number(value);

    return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
  }

  function bindActivityEvents() {
    var activityEvents = ['click', 'keydown', 'mousedown', 'mousemove', 'scroll', 'touchstart'];

    activityEvents.forEach(function (eventName) {
      globalObject.addEventListener(eventName, onUserActivity, { passive: true });
    });
  }

  function bindLifecycleEvents() {
    globalObject.addEventListener('storage', function (event) {
      if (event.key === lastActivityKey) {
        var sharedTimestamp = parseTimestamp(event.newValue);

        if (sharedTimestamp > lastActivityAt) {
          lastActivityAt = sharedTimestamp;
          scheduleExpiryCheck();
        }
      }

      if (event.key === logoutKey && event.newValue) {
        triggerLogout(defaultLogoutUrl, false);
      }
    });

    globalObject.addEventListener('focus', syncTimersFromStorage);
    document.addEventListener('visibilitychange', function () {
      if (!document.hidden) {
        syncTimersFromStorage();
      }
    });
  }

  function onUserActivity() {
    var now = Date.now();

    if (now - lastInteractionAt < activityThrottleMs) {
      return;
    }

    lastInteractionAt = now;
    recordActivity({ touchServer: true, timestamp: now });
  }

  function recordActivity(options) {
    options = options || {};

    var timestamp = options.timestamp || Date.now();
    lastActivityAt = timestamp;

    try {
      globalObject.localStorage.setItem(lastActivityKey, String(timestamp));
    } catch (error) {
      // Ignore storage write failures.
    }

    scheduleExpiryCheck();

    if (options.touchServer === false) {
      return;
    }

    touchSession();
  }

  function touchSession() {
    var now = Date.now();

    if (now - lastTouchAt < touchIntervalMs || typeof globalObject.fetch !== 'function') {
      return;
    }

    lastTouchAt = now;

    globalObject.fetch(touchUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-AIA-Expect-Json': '1',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: '{}',
      keepalive: true,
    })
      .then(function (response) {
        if (response.status === 401) {
          return response.json()
            .then(function (payload) {
              triggerLogout((payload && payload.redirect) || defaultLogoutUrl, true);
              return null;
            })
            .catch(function () {
              triggerLogout(defaultLogoutUrl, true);
              return null;
            });
        }

        if (!response.ok) {
          throw new Error('Session touch failed');
        }

        return response.json();
      })
      .catch(function () {
        lastTouchAt = 0;
      });
  }

  function syncTimersFromStorage() {
    if (redirecting) {
      return;
    }

    try {
      var sharedTimestamp = parseTimestamp(globalObject.localStorage.getItem(lastActivityKey));

      if (sharedTimestamp > lastActivityAt) {
        lastActivityAt = sharedTimestamp;
      }
    } catch (error) {
      // Ignore storage read failures.
    }

    scheduleExpiryCheck();
  }

  function scheduleExpiryCheck() {
    if (redirecting) {
      return;
    }

    if (expiryTimerId) {
      globalObject.clearTimeout(expiryTimerId);
    }

    var remainingMs = (lastActivityAt + timeoutMs) - Date.now();

    if (remainingMs <= 0) {
      triggerLogout(defaultLogoutUrl, true);
      return;
    }

    expiryTimerId = globalObject.setTimeout(function () {
      triggerLogout(defaultLogoutUrl, true);
    }, remainingMs);
  }

  function triggerLogout(redirectUrl, broadcast) {
    if (redirecting) {
      return;
    }

    redirecting = true;

    if (broadcast !== false) {
      try {
        globalObject.localStorage.setItem(logoutKey, String(Date.now()));
      } catch (error) {
        // Ignore storage write failures.
      }
    }

    globalObject.location.replace(redirectUrl || defaultLogoutUrl);
  }
})();
