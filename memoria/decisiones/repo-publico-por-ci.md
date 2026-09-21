---
capa: wiki
tipo: decision
estado: vigente
fecha: 2026-09-21
areas: [deploy, qa, proceso]
fuente: sesion
resumen: "El repo de GitHub de lps-aia es público a propósito, para tener CI ilimitado; no es una alarma, pero todo lo que se sube queda a la vista"
---
El remoto de `lps-aia` (`jbenite7/lastplanneraia-construccion`) es **público a propósito**. Felipe lo
decidió para tener minutos de GitHub Actions ilimitados: los repos públicos no consumen la cuota de
Actions y los privados sí. Él lo confirmó en el chat el 2026-09-21, cuando una sesión lo señaló como
posible choque con la línea roja «los datos de AIA no salen de AIA».

**Why:** el CI (`.github/workflows/ci.yml`) es el gate de todo merge desde el 2026-08-26
([[AGENTS]] §Publicación) y corre matrices por tema en cada PR. Con cuota limitada, ese gate se
quedaría sin minutos.

**How to apply:**
- No lo vuelvas a levantar como alarma. La configuración global presume repos privados «salvo que
  una verificación muestre lo contrario»; en este repo la verificación lo muestra público, y está
  bien que así sea.
- Lo que sí importa: **todo lo que entra al repo queda público**. Antes de subir algo, pregúntate si
  puede ser público. Los secretos viven solo en `.env`, que no se versiona ([[CLAUDE]] §Runtime).
  Los datos reales de obra no van en fixtures ni en evidencias versionadas.
- Si algo no debe ser público, va a otro lugar: un repo privado aparte o un artefacto privado. El
  tablero de specs, por ejemplo, tiene desde el 2026-09-21 su propio repo privado, `jbenite7/tablero-specs`, y guarda sus datos en
  la `db` de un artefacto privado.
