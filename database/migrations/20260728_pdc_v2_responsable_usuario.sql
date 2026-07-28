-- Responsable de paquete: de texto libre a usuario del proyecto (previo a B1 · Seguimiento).
--
-- `responsable` era VARCHAR(100) escrito a mano. B1 va a colgar de este campo el filtro «mis
-- paquetes» y las notificaciones, y una cadena a mano no identifica a nadie: «Juan Pérez»,
-- «juan perez» y «J. Pérez» son tres personas para la base y una sola en la obra.
--
-- Se hace AHORA porque la tabla está vacía: no hay ni un responsable escrito, así que no hay
-- backfill que hacer ni nombres que reconciliar, y la columna vieja se puede quitar sin coste.
-- Con datos reales dentro, ninguna de las dos cosas sería gratis.
--
-- ON DELETE SET NULL implementa la decisión «si a alguien lo sacan, sus paquetes quedan sin
-- responsable» para el caso de borrar la ficha del usuario. Salir de `project_members` NO borra
-- al usuario, así que ese caso no lo cubre la FK: lo resuelve la lectura marcando al responsable
-- como huérfano. Es deliberado — no se borra el dato, se señala.
ALTER TABLE pdc_plan_paquete
  ADD COLUMN responsable_user_id INT NULL DEFAULT NULL AFTER duracion_provisional,
  ADD COLUMN responsable_asignado_por VARCHAR(100) NOT NULL DEFAULT '' AFTER responsable_user_id,
  ADD COLUMN responsable_asignado_at DATETIME NULL DEFAULT NULL AFTER responsable_asignado_por,
  ADD KEY idx_ppp_responsable (project_id, responsable_user_id),
  ADD CONSTRAINT fk_ppp_responsable FOREIGN KEY (responsable_user_id)
      REFERENCES general_usuarios (id) ON DELETE SET NULL,
  DROP COLUMN responsable;
