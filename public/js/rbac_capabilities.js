/**
 * RBAC Capabilities Helper
 *
 * Centraliza las reglas de negocio UI basadas en los roles canónicos de la nueva arquitectura RBAC.
 * Reemplaza la lógica espagueti de `if(permiso == "P")` dispersa en las vistas legacy.
 */

const RbacCapabilities = {
  /**
   * Determina si un usuario tiene capacidad de escritura y edición total en el Last Planner System (PG, PI, PS).
   * @param {string} role - El código de rol del usuario actual (ej. 'A', 'D', 'R', 'V').
   * @param {number} currentWeek - La semana que se está visualizando.
   * @param {number} maxWeek - La semana máxima actual del proyecto (Semanas_Activas).
   * @returns {boolean}
   */
  canEditLps: function (role, currentWeek, maxWeek) {
    // Validation of data consistency
    if (!role) return false;

    // Roles con privilegios administrativos (pueden editar semanas históricas)
    if (role === 'A' || role === 'D') {
      return true;
    }

    // Roles técnicos/especialistas (solo pueden editar semanas actuales/futuras)
    // La tolerancia típica es Max_Semana - 2
    var isEditableWeek = maxWeek - 2 >= currentWeek ? false : true;

    if (isEditableWeek) {
      const allowedRoles = ['R', 'DCV'];
      return allowedRoles.includes(role);
    }

    return false;
  },

  /**
   * Determina si un usuario puede ver botones de acceso exclusivo administrativo u Oficina Técnica
   * (Plan de Compras). Absorbió a `canManageContracts`, que era su alias exacto y nombraba un
   * módulo eliminado con el PDC v1 el 2026-08-04 (RBAC-A, colapsado el 2026-08-10).
   */
  canManagePdC: function (role) {
    // Espejo de RbacManager::getCapabilities()['canManagePdC'] (src/Security/RbacManager.php:40).
    // El Residente entra por decisión del usuario del 2026-08-10: en obra también gestiona el Plan
    // de Compras. Antes decía ['A','D','OT'] con un «ajustar si R necesita» que nadie resolvió, y esa
    // divergencia con el servidor la destapó el gate `npm run test:rbac-parity`.
    // Si cambias esta lista, cambia también la del servidor: el gate falla si se separan.
    const allowedRoles = ['A', 'D', 'OT', 'R'];
    return allowedRoles.includes(role);
  },

  canManageWeeks: function (role) {
    return ['A', 'D', 'OT', 'R', 'DCV'].includes(role);
  },

  canEditPastGeneralProgram: function (role) {
    return ['A', 'D'].includes(role);
  },

  canManageGeneralProgram: function (role) {
    return ['A', 'D', 'R', 'DCV'].includes(role);
  },

  canManageMediumTermProgram: function (role) {
    return ['A', 'D', 'R', 'DCV'].includes(role);
  },

  canManageWeeklyProgram: function (role) {
    return ['A', 'D', 'R', 'S', 'G', 'SG'].includes(role);
  },

  /**
   * Determina si un rol es estrictamente de solo lectura (Visitante, Subcontratista inicial).
   */
  isReadOnly: function (role) {
    const readOnlyRoles = ['V', 'C'];
    return readOnlyRoles.includes(role);
  },

  /**
   * Traducción amigable del rol (Fallback client-side si se requiere)
   */
  getRoleName: function (role) {
    const dictionary = {
      A: 'Administrador de Sistema',
      D: 'Director de Proyecto',
      R: 'Residente de Obra',
      DCV: 'Profesional DCV',
      OT: 'Oficina Técnica',
      G: 'Residente Ambiental',
      S: 'Residente SST',
      SG: 'Residente Socio-Ambiental',
      C: 'Subcontratista',
      V: 'Visitante',
    };
    return dictionary[role] || 'Desconocido';
  },
};

function readRbacRole() {
  var el = document.getElementById('permiso_canonico');
  return String(el ? el.value : '').trim().toUpperCase();
}

function buildLegacyCapabilities() {
  var resolveRole = function () {
    return readRbacRole();
  };

  var legacyCaps = {};

  Object.defineProperties(legacyCaps, {
    canManageWeeks: {
      enumerable: true,
      get: function () {
        return RbacCapabilities.canManageWeeks(resolveRole());
      },
    },
    canManageGeneralProgram: {
      enumerable: true,
      get: function () {
        return RbacCapabilities.canManageGeneralProgram(resolveRole());
      },
    },
    canEditPastGeneralProgram: {
      enumerable: true,
      get: function () {
        return RbacCapabilities.canEditPastGeneralProgram(resolveRole());
      },
    },
    canManageMediumTermProgram: {
      enumerable: true,
      get: function () {
        return RbacCapabilities.canManageMediumTermProgram(resolveRole());
      },
    },
    canManageWeeklyProgram: {
      enumerable: true,
      get: function () {
        return RbacCapabilities.canManageWeeklyProgram(resolveRole());
      },
    },
    canManagePdC: {
      enumerable: true,
      get: function () {
        return RbacCapabilities.canManagePdC(resolveRole());
      },
    },
    isReadOnly: {
      enumerable: true,
      get: function () {
        return RbacCapabilities.isReadOnly(resolveRole());
      },
    },
  });

  return legacyCaps;
}

// Exponer globalmente para las vistas legacy
window.RbacCapabilities = RbacCapabilities;
window.rbacCapabilities = buildLegacyCapabilities();
