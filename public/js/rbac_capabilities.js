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
      const allowedRoles = ['R', 'DCV', 'OT', 'G', 'S', 'SG'];
      return allowedRoles.includes(role);
    }

    return false;
  },

  /**
   * Determina si un usuario puede ver botones de acceso exclusivo administrativo u Oficina Técnica (ej. contratos, PDC).
   */
  canManageContracts: function (role) {
    const allowedRoles = ['A', 'D', 'OT']; // Asumiendo que Admin, Director y Oficina Técnica gestionan esto. Ajustar si R necesita.
    return allowedRoles.includes(role);
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

// Exponer globalmente para las vistas legacy
window.RbacCapabilities = RbacCapabilities;
