# Goal: escenarios operativos de /contratos/

Resolver funcionalmente los escenarios operativos del modulo `/contratos/`, enfocando el trabajo en modalidad, paquetes, recursos, cantidades de contratos por paquete, duraciones contractuales, trazabilidad semanal, permisos, semi-auto y evidencia. `/pdc/` solo entra como receptor existente de duraciones y recalculo cuando `/contratos/` ya debe alimentarlo; proveedores quedan fuera de este goal.

La comprension compartida esta en `facts.md`.

El plan de ejecucion aprobado esta en `plan.md`.

Done condition: el goal queda listo cuando los facts aceptados estan implementados o explicitamente verificados como fuera de alcance, el plan aprobado se ejecuta con pruebas PHP, Playwright, guardar/recargar, snapshots y evidencia visual de los escenarios principales, sin introducir seleccion ni mantenimiento de proveedores en `/contratos/`.
