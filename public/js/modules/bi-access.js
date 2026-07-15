(function () {
  function getValue(id) {
    var element = document.getElementById(id);
    return element ? element.value : '';
  }

  function resolveWeek() {
    return getValue('semana')
      || getValue('semana_PHP')
      || getValue('Max_Semana')
      || window.__BI_ACCESS__?.semana
      || '';
  }

  function resolveProjectId() {
    return window.__BI_ACCESS__?.projectId || getValue('project_id') || '';
  }

  function resolveLink(linkOrModule) {
    if (linkOrModule instanceof Element) return linkOrModule;
    var module = String(linkOrModule || window.__BI_ACCESS__?.module || 'control-tower');
    return document.querySelector('[data-bi-access-link="' + module + '"]');
  }

  function buildUrl(linkOrModule) {
    var link = resolveLink(linkOrModule);
    var baseUrl = link?.getAttribute('href')
      || link?.dataset.biBaseUrl
      || window.__BI_ACCESS__?.baseUrl;
    if (!baseUrl) return '';

    var url = new URL(baseUrl, window.location.origin);
    var projectId = resolveProjectId();
    var week = resolveWeek();
    var hasMultiProject = Array.from(url.searchParams.keys())
      .some(function (key) { return key.indexOf('project_ids[') === 0; });
    if (projectId && !hasMultiProject) {
      url.searchParams.set('project_id', projectId);
    }
    if (week) url.searchParams.set('semana', week);

    return url.pathname + url.search;
  }

  function syncAccessLinks() {
    document.querySelectorAll('[data-bi-access-link]').forEach(function (link) {
      var url = buildUrl(link);
      if (url) link.setAttribute('href', url);
    });
  }

  window.BiAccess = { buildUrl: buildUrl, syncAccessLinks: syncAccessLinks };

  document.addEventListener('DOMContentLoaded', syncAccessLinks);
})();
