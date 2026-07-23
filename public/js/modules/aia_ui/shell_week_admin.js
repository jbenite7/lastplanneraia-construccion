((global) => {
  function notice(method, message, title) {
    if (global.AIA?.Notice && typeof global.AIA.Notice[method] === "function") {
      return global.AIA.Notice[method](message, title);
    }
    global.alert(message);
    return Promise.resolve();
  }

  function postForm(url, fields) {
    const body = new URLSearchParams(fields);
    return fetch(url, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
      body: body.toString(),
    }).then((res) => {
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return res.json();
    });
  }

  function setBusy(button, busy) {
    if (!button) return;
    button.disabled = busy;
    button.setAttribute("aria-busy", String(busy));
  }

  function formatEnd(startIso) {
    const d = new Date(`${startIso}T00:00:00`);
    if (Number.isNaN(d.getTime())) return "";
    d.setDate(d.getDate() + 6);
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    return `${d.getFullYear()}-${mm}-${dd}`;
  }

  function init() {
    const dataEl = document.getElementById("shellWeekMenusData");
    if (!dataEl) return;
    let data;
    try {
      data = JSON.parse(dataEl.textContent);
    } catch (_e) {
      return;
    }

    const createDialog = document.getElementById("shellWeekCreateDialog");
    const createOpen = document.getElementById("shellWeekCreateOpen");
    const dateInput = document.getElementById("shellWeekCreateDate");
    const preview = document.getElementById("shellWeekCreatePreview");
    const createSubmit = document.getElementById("shellWeekCreateSubmit");

    function refreshPreview() {
      if (!dateInput || !preview) return;
      const end = formatEnd(dateInput.value);
      preview.textContent = end ? `Irá del ${dateInput.value} al ${end}.` : "";
    }

    if (createOpen && createDialog) {
      createOpen.addEventListener("click", () => {
        refreshPreview();
        createDialog.showModal();
      });
    }
    if (dateInput) dateInput.addEventListener("input", refreshPreview);

    if (createSubmit && createDialog) {
      createSubmit.addEventListener("click", () => {
        setBusy(createSubmit, true);
        postForm("/legacy/funciones_generales/php/verificarCICActualizada.php", {
          db: data.db,
          semana: String(data.currentWeek),
        })
          .then((faltan) => {
            if (Number(faltan) !== 0) {
              return notice(
                "warning",
                `No se pueden crear nuevas semanas hasta realizar las Calificaciones Integrales ${faltan}.`,
                "Calificación pendiente",
              ).then(() => {
                global.location.assign(data.cicPath);
              });
            }
            return postForm(`/legacy/funciones_generales/php/nueva_semana.php?db=${encodeURIComponent(data.db)}`, {
              f_inicio_sem: dateInput ? dateInput.value : "",
              opcion: "nueva_sem",
              _csrf_token: data.csrfToken,
            }).then((info) => {
              if (info && info.respuesta === "ERROR") {
                return notice("error", info.mensaje || "No se pudo crear la semana.");
              }
              const semana = Number(info?.[0]);
              const confirmada = Number(info?.[3]);
              if (confirmada === 0 && semana > 0 && !data.esAdmin) {
                return notice(
                  "warning",
                  `No se puede crear la Semana ${semana + 1} hasta confirmar los compromisos de la Semana ${semana}.`,
                  "Semana bloqueada",
                ).then(() => {
                  global.cambiarSemanaSesion(semana, "/programacion-semanal");
                });
              }
              createDialog.close();
              global.cambiarSemanaSesion(semana, "/programa-general");
              return undefined;
            });
          })
          .catch((err) => notice("error", `Error al crear la semana: ${err.message}`))
          .finally(() => setBusy(createSubmit, false));
      });
    }

    const deleteDialog = document.getElementById("shellWeekDeleteDialog");
    const deleteSubmit = document.getElementById("shellWeekDeleteSubmit");
    let deleteWeek = data.maxSemana;

    document.addEventListener("click", (event) => {
      const trigger = event.target.closest("[data-shell-delete-week]");
      if (!trigger || !deleteDialog) return;
      deleteWeek = parseInt(trigger.getAttribute("data-shell-delete-week"), 10);
      deleteDialog.showModal();
    });

    if (deleteSubmit && deleteDialog) {
      deleteSubmit.addEventListener("click", () => {
        setBusy(deleteSubmit, true);
        postForm(`/legacy/funciones_generales/php/eliminar_semana.php?db=${encodeURIComponent(data.db)}`, {
          semana: String(deleteWeek),
          opcion: "eliminar_sem",
          _csrf_token: data.csrfToken,
        })
          .then((info) => {
            if (info && info.respuesta === "ERROR") {
              return notice("error", info.mensaje || "No se pudo eliminar la semana.");
            }
            if (info && info.puedeEliminar === "SI") {
              deleteDialog.close();
              const target = deleteWeek - 1;
              if (target >= 1) {
                global.cambiarSemanaSesion(target, global.location.pathname);
              } else {
                // Última semana del proyecto eliminada: recargar para que el servidor recalcule el contexto.
                global.location.reload();
              }
              return undefined;
            }
            return notice(
              "warning",
              `Solo se puede eliminar la semana máxima del proyecto (Semana ${info?.maxSemana}).`,
              "Acción no permitida",
            );
          })
          .catch((err) => notice("error", `Error al eliminar la semana: ${err.message}`))
          .finally(() => setBusy(deleteSubmit, false));
      });
    }

    document.querySelectorAll("[data-aia-dialog-close]").forEach((btn) => {
      const dialog = btn.closest("dialog");
      if (dialog && (dialog.id === "shellWeekCreateDialog" || dialog.id === "shellWeekDeleteDialog")) {
        btn.addEventListener("click", () => dialog.close());
      }
    });
  }

  global.AiaShellWeekAdmin = { init };
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init, { once: true });
  } else {
    init();
  }
})(window);
