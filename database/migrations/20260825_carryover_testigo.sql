-- 2026-08-25 — Testigo del arrastre de avance semanal hacia Programa General.
--
-- Problema que resuelve: `WeeklyRealProgressCarryoverService` protege lo que el residente
-- escribe a mano en `Ejecutado`, y para saber si hubo edicion comparaba contra el acumulado
-- de la semana origen. Pero la celda destino ya trae sumado el avance de la semanal, asi que
-- SIEMPRE difiere del origen en cuanto hay algo reportado: desde la segunda corrida el
-- servicio confunde su propia escritura con una edicion ajena y congela la fila. Medido en
-- produccion el 2026-08-25.
--
-- `Ejecutado_Carryover` guarda el ultimo valor que escribio el propio arrastre. Si `Ejecutado`
-- sigue igual a ese testigo, nadie lo edito y se puede recalcular; si difiere, lo movio el
-- residente y manda el.
--
-- Aditiva y reversible: no toca datos existentes. NULL significa "esta fila nunca paso por el
-- arrastre nuevo", y para esas el servicio mantiene el criterio anterior.
--
-- Reversion:  ALTER TABLE programa_consolidado DROP COLUMN Ejecutado_Carryover;

ALTER TABLE `programa_consolidado`
  ADD COLUMN `Ejecutado_Carryover` float DEFAULT NULL
  COMMENT 'Ultimo valor escrito por WeeklyRealProgressCarryoverService; NULL = nunca arrastrada'
  AFTER `Ejecutado_Siguiente_Semana`;
