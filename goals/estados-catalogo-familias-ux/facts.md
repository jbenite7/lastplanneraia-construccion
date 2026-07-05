# Facts

- Cada familia, alias y elemento contractual del catálogo muestra un estado entendible para usuario: Crea actividades, Se gestiona en Contratos, Es otro nombre de..., Necesita decisión o No usar.
- La UI no usa Inactivo como explicación final cuando el sistema puede derivar un motivo más claro desde aliases, elementos contractuales, reglas o revisión pendiente.
- El asistente semi-automático muestra motivo claro, estado visible, siguiente acción y paquete sugerido cuando una propuesta no se puede aplicar automáticamente.
- Los usuarios finales pueden ver la explicación y asignar paquetes en Contratos, pero no ven acciones de catálogo global ni detalle técnico.
- Los usuarios Admin pueden mantener una familia en Listado, pasarla a Contratos, crear una opción contractual guiada y crear o reasignar reglas sin tocar SQL.
- Cuando una familia existe pero no tiene paquetes configurados, Admin tiene un flujo guiado para crear la opción contractual con modalidad, paquetes, cantidades por defecto y duraciones requeridas si faltan.
- El catálogo admin incluye un reporte completo de familias, aliases y elementos contractuales con estado derivado, motivo y siguiente acción recomendada.
- El flujo Listado -> Contratos -> PDC se verifica desde cero usando el contexto propio de esos módulos, sin asumir dependencia de semanas activas de Last Planner.
- La verificación E2E cubre al menos Pisos y Enchapes, Campamento de Obra, una familia operativa normal y el flujo hasta generación de Plan de Compras.
- El objetivo se implementa como un solo goal, con alcance completo de UX, acciones Admin, reporte de catálogo total y evidencia E2E.
