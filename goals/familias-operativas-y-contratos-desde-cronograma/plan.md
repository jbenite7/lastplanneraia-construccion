# Plan de implementación: familias operativas y contratos desde cronograma

## Enfoque

Separar formalmente dos decisiones que hoy están mezcladas:

- `/listado-actividades/` decide familias operativas: trabajos ejecutables y medibles del cronograma.
- `/contratos/` decide paquetes contractuales: compras, insumos, equipos, suministros, subcontratos y paquetes que permiten ejecutar esos trabajos.

La familia operativa y el contrato pueden llamarse parecido o igual, pero no significan lo mismo. El motor debe conservar esa diferencia para no convertir compras o paquetes en actividades.

## Pasos

1. Crear una política compartida de familias operativas vs elementos contractuales.
   - Archivo nuevo sugerido: `src/Support/OperationalFamilyPolicy.php`.
   - Debe exponer:
     - `normalizeOperationalFamily(string $name): string`.
     - `isContractualOnlyFamily(string $name): bool`.
     - `contractualPackageHints(string $name): array`.
     - `isOperationalFamilyAllowedForListado(string $name): bool`.
   - Debe incluir como regla inicial:
     - `Enchapes Ceramicos en Muros` => `Pisos y Enchapes`.
     - `Red RCI` y `Red Contra Incendio - Piping` => `Red de Extinción`.
     - Ejemplos contractuales iniciales excluidos de listado: `Acero de Refuerzo y Estructural`, `Aligerantes Perdidos y Recuperables`, `Contenedores`, `Encofrado y Obra Falsa`, `Equipos de Extincion`, `Estuco`, `Fachada HPL, Vidrio y Aluminio`, `Geodren`, `Losas de Cimentacion`, `Luminarias y Artefactos Electricos`.
   - Verificación:
     - Test unitario de política con esos aliases y exclusiones.

2. Consolidar la biblioteca de familias desde `programa_consolidado` y `actividades`.
   - Extender `docs/qa/pdc_family_corpus_extractor.php` para leer también `actividades`, además de `programa_consolidado`, PDC y Excel.
   - La matriz/corpus debe clasificar cada nombre como:
     - `familia_operativa`.
     - `elemento_contractual`.
     - `alias_de_familia_operativa`.
     - `dudoso`.
   - La salida debe dejar claro que los elementos contractuales alimentan `/contratos/`, no `/listado-actividades/`.
   - Regenerar:
     - `docs/qa/matriz-validacion-humana.xlsx`.
     - `docs/qa/matriz-validacion-humana.summary.json`.
     - `docs/qa/matriz-validacion-humana.summary.md`.
     - `docs/qa/corpus-maestro-familias-pdc.md`.
   - Verificación:
     - La matriz no muestra elementos contractuales como opciones de `familia_correcta`.
     - La matriz sí muestra `Pisos y Enchapes` y `Red de Extinción`.
     - El resumen reporta cuántos casos fueron operativos, contractuales, aliases y dudosos.

3. Ajustar `ActivityMatcher` para devolver intención operativa.
   - Archivo: `src/Support/ActivityMatcher.php`.
   - Después de encontrar una familia, aplicar `OperationalFamilyPolicy`.
   - Si la familia detectada es contractual-only:
     - no debe regresar como match listo para `/listado-actividades/`;
     - debe conservar metadata de contrato sugerido para `/contratos/`.
   - Si la familia detectada es alias:
     - debe devolver la familia operativa canónica.
   - Verificación:
     - Pruebas directas para `Acero`, `Aligerantes`, `Encofrado`, `Luminarias`, `Enchapes`, `Red RCI`.

4. Ajustar `/listado-actividades/`.
   - Archivo: `src/Services/SemiAutoService.php`, método `buildListadoSuggestions`.
   - Cuando `ActivityMatcher` marque una coincidencia como contractual-only:
     - no crear propuesta `create_activity`;
     - crear propuesta de revisión o metadata trazable indicando que debe pasar a `/contratos/`.
   - Las propuestas `ready` de listado no deben contener familias contractuales-only.
   - Las actividades fuente deben seguir guardándose en `actividad_programa_fuentes` cuando se aplique una actividad operativa.
   - Verificación:
     - JMC semana 5 y Da Porto semana 1 no deben tener propuestas listas de listado con los ejemplos contractuales.
     - Las familias operativas canónicas sí deben seguir apareciendo cuando el cronograma lo justifique.

5. Ajustar `/contratos/` para autogenerar elementos contractuales faltantes.
   - Archivo: `src/Services/SemiAutoService.php`, método `buildContratosSuggestions`.
   - Fuente base: `general_dias_procesos_contratacion`.
   - Mantener `general_pdc_family_contract_options` y `general_pdc_family_contract_option_items` como configuración preferente cuando exista.
   - Si una familia operativa no tiene opción completa, poblar o sugerir paquetes desde:
     - `general_dias_procesos_contratacion.paqueteContratacion`;
     - hints de `OperationalFamilyPolicy`;
     - fuentes de `actividad_programa_fuentes`.
   - La propuesta contractual debe llenar `tipoContrato`, paquetes `SI/S/MO/OC`, `numeroSubcontratos`, `confianza_deteccion` y conservar explicación visible para usuario final.
   - Verificación:
     - Para una actividad operativa con fuentes que mencionan acero, aligerante, encofrado, luminarias u otro elemento contractual, `/contratos/` propone el paquete correspondiente.
     - Si ya hay configuración familiar existente, no se duplica ni se pisa.

6. Ajustar la compuerta de calidad.
   - Archivo: `src/Support/SemiAutoQualityGate.php`.
   - En listado:
     - `ready` queda prohibido si la familia es contractual-only.
     - Los aliases operativos deben evaluarse bajo su familia canónica.
   - En contratos:
     - una propuesta puede quedar `ready` si tiene paquete contractual, fuente auditable, tipo de paquete y fechas calculables.
   - Verificación:
     - Test de quality gate para bloqueo en listado y permiso en contratos.

7. Actualizar pruebas PHP.
   - Actualizar:
     - `tests/test_human_validation_matrix.php`.
     - `tests/test_semi_auto_quality_gate.php`.
     - `tests/test_semi_auto_service.php`.
   - Agregar test nuevo si conviene:
     - `tests/test_operational_family_policy.php`.
     - `tests/test_contractual_family_routing.php`.
   - Casos mínimos:
     - `Enchapes Ceramicos en Muros` se normaliza a `Pisos y Enchapes`.
     - `Red RCI` se normaliza a `Red de Extinción`.
     - ejemplos amarillos no pasan como familia de listado.
     - ejemplos amarillos sí producen visibilidad o paquete en contratos.
     - JMC y Da Porto quedan cubiertos.

8. Actualizar pruebas E2E y evidencia.
   - Usar pruebas Playwright existentes de semi-auto o crear una enfocada.
   - Correr flujo en:
     - Optimización Aeropuerto JMC, semana 5.
     - Da Porto, semana 1.
   - Guardar evidencia en `docs/qa/evidence/...`:
     - resumen JSON sanitizado;
     - capturas;
     - recording o trace local si la suite lo permite.
   - Verificación:
     - La evidencia muestra que listado no propone contractuales como actividades y contratos sí conserva esas necesidades.

9. Verificación final.
   - `docker compose exec app php -l src/Support/OperationalFamilyPolicy.php`
   - `docker compose exec app php -l src/Support/ActivityMatcher.php`
   - `docker compose exec app php -l src/Services/SemiAutoService.php`
   - `docker compose exec app php -l src/Support/SemiAutoQualityGate.php`
   - `docker compose exec app php docs/qa/pdc_family_corpus_extractor.php --matrix`
   - `docker compose exec app php docs/qa/pdc_family_corpus_extractor.php --verify-matrix`
   - `docker compose exec app php tests/test_human_validation_matrix.php`
   - `docker compose exec app php tests/test_semi_auto_quality_gate.php`
   - `docker compose exec app php tests/test_semi_auto_service.php`
   - `docker compose exec app php tests/test_operational_family_policy.php`
   - `docker compose exec app vendor/bin/phpstan analyse src public/index.php --memory-limit=1G`
   - Playwright E2E para JMC y Da Porto.

## Riesgos

- La tabla `general_pdc_familias` mezcla familias y conceptos contractuales. El plan evita depender de renombrarla de inmediato usando una política explícita de clasificación.
- `general_dias_procesos_contratacion` tiene paquetes contractuales, no familias. Debe usarse para `/contratos/`, no para validar familias de listado.
- Algunas palabras pueden ser familia operativa en un proyecto y contrato en otro. Si la evidencia no es clara, la propuesta debe quedar `Por revisar`, no `Lista`.
- Si se autogeneran contratos sin trazabilidad, el usuario no podrá validar por qué aparecieron. Toda propuesta de `/contratos/` debe explicar qué fuente de cronograma o actividad la originó.

## Condición de terminado

El objetivo queda terminado cuando `/listado-actividades/` solo propone familias operativas canónicas del cronograma, `/contratos/` genera o conserva los elementos contractuales excluidos de listado, la matriz/corpus refleja esa separación, y las pruebas PHP/E2E sobre JMC y Da Porto lo demuestran con evidencia guardada.
