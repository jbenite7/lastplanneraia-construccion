# AGENTS.md — frontend React

Reglas propias de esta carpeta. Complementan el AGENTS.md de la raíz, que sigue mandando.

## Contratos

- **Nadie llama `fetch` directo.** Todo pasa por `src/lib/api/cliente.ts`, que valida la
  respuesta contra su esquema Zod y lanza un error que nombra endpoint y campo si no cuadra.
- **El esquema Zod es la única definición del tipo.** Los tipos TypeScript salen de él con
  `z.infer`. Nunca se escribe una interfaz a mano en paralelo a un esquema: se desincronizan.
- **Los colores, radios y sombras salen de `public/css/tokens.css`.** No se declara un color
  literal en ningún componente. Si falta un token, se añade allá y se documenta, no se inventa aquí.

## Estilo

- Archivos de ~300 líneas como guía, no como regla dura. Un archivo cohesionado de 340 está bien;
  uno de 800 casi nunca lo está.
- Nombres del dominio, en español, como en `GLOSARIO.md`: `SemanaComprometida`, no `WeekData`.
- Un archivo, una responsabilidad.

## Pruebas

- Vitest para lógica y componentes: `npm --prefix frontend test`.
- Cada endpoint nuevo lleva su prueba de contrato del lado PHP (`tests/test_api_*_contract.php`).
- **No hay gate de cobertura mínima**, a propósito: empuja a escribir pruebas que suben el número
  sin atrapar nada.
