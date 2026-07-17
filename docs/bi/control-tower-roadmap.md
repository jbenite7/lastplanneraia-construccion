# Roadmap BI Control Tower

## Curva S Valor Ganado financiero

Estado: diferida para una fase posterior.

Motivo: el Programa General todavia no tiene una fuente financiera confiable por actividad, contrato, paquete o flujo de caja. Con los datos actuales solo se puede calcular avance ponderado por `cantidad_ppto` o duracion, que duplica conceptualmente la Curva S Ejecucion y no representa dinero.

Para reactivarla como grafica visible se requiere:

- Presupuesto o costo contractual por actividad, paquete, capitulo o contrato.
- Relacion estable entre `programa_consolidado` y la fuente financiera.
- Definicion de valor planeado, valor ganado y costo real en unidades monetarias.
- Pruebas con proyecto unico, multiproyecto, rango de fechas, semana, subcontratista y responsable.
- Tooltip y linaje que indiquen fuente financiera, unidad monetaria y formula.

Mientras no exista esa fuente, la grafica no debe mostrarse en Programa General.
