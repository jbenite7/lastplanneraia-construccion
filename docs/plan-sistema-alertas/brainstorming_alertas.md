# Brainstorming Final: Sistema de Alertas AIA +CERTEZA

Este documento consolida la estrategia proactiva para Last Planner System (LPS) en AIA, integrando predictividad, fiabilidad y tendencias de ejecución.

---

## 🧐 Glosario de Conceptos Proactivos

### 1. Actividad "Zombie" (Estancamiento Silencioso)
- **Definición**: Una actividad con avance > 0% que no ha reportado datos nuevos de ejecución en las últimas 2 semanas.
- **Riesgo**: Genera una falsa sensación de progreso mientras consume tiempo del cronograma.

### 2. Shadow PPC (PPC Fantasma)
- **Definición**: Recálculo del PPC eliminando las restricciones de planificación.
- **Propósito**: Diferenciar si el incumplimiento se debe a una falla del contratista (PPC bajo) o a una falla en la liberación de la "cancha" por parte de la administración (Shadow PPC alto).

### 3. Proyecciones Asintóticas 2.0
- **Definición**: Algoritmo que usa la Curva S histórica y el remanente de obra para predecir la **Fecha Final Real de Entrega**.
- **Valor**: Proyecta colisiones de cronograma con semanas de antelación.

### 4. Capacidad Operativa
- **Medición**: $Capacidad = \frac{Cantidad Total PPTO}{Dias Calendario Totales}$.
- **Alerta**: Si un compromiso semanal supera significativamente la capacidad teórica, se marca como una **Sobre-promesa** de alto riesgo.

---

## 🚀 TOP 5 - Selección Final (Fase 7 - Corto Plazo)

1. **Asistente de Turno AIA (Email/Panel)**
   - Notificación diaria (Snapshot) que detecta desviaciones en tiempo real (actividades Zombie, retrasos de inicio, sobre-promesas).

2. **Score de Fiabilidad 360 (PPC + CIC)**
   - Badge dinámico (A, B, C) que combina el PPC (Porcentaje de Plan Cumplido) de las últimas 4 semanas con la Calificación Integral de Contratista (CIC).

3. **Semáforo de Restricciones "Countdown"**
   - Transiciones visuales de urgencia:
     - **Verde**: > 15 días.
     - **Amarillo**: 7 días.
     - **Naranja**: 5 días.
     - **Rojo**: 2 días para el compromiso.

4. **Auditoría de Silencios**
   - Alerta automática ante la falta de captura de datos en actividades que deberían estar en ejecución.

5. **Checklist "Gate Zero" (Viabilidad de Semana)**
   - Bloqueo/Alerta antes de confirmar la programación semanal si existen restricciones no liberadas para las actividades comprometidas.

---

## 🏗️ Lógica Técnica y Tendencias

### Tendenciómetro (Media Móvil Ponderada)
Se calcula promediando los últimos 4 eventos de cumplimiento, dando mayor peso al más reciente para detectar "caídas de ritmo" tempranas:
- $\text{Tendencia} = (PPC_{S1} \cdot 0.4) + (PPC_{S2} \cdot 0.3) + (PPC_{S3} \cdot 0.2) + (PPC_{S4} \cdot 0.1)$

### UI: Tag "Urgente" Dinámico
Elemento visual inyectado en Handsontable mediante renderers OKLCH para destacar actividades con un atraso acumulado $> 10\%$.

---

## ⏳ Horizonte Mediano Plazo (Pendiente Ruta Crítica)
- Predictor de Efecto Dominó (Dependencias).
- Alerta de Predecesora en Riesgo.
- Mapa de Calor de Embudos de Restricciones.
- Botón "Poner el Ojo" (Vigilancia Especial).
