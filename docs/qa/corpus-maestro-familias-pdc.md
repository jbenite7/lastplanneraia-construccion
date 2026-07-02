# Corpus maestro de familias, asociaciones y patrones

Generado: `2026-07-02T00:04:12-05:00`.

Este documento es evidencia para revisar familias y asociaciones. No debe usarse como verdad automática para agrupar actividades en `/listado-actividades/`.

Criterio de lectura: la familia candidata se toma primero del nombre real de la actividad. El capítulo/contexto solo se usa como respaldo y esos casos quedan marcados como duda, porque pueden ser ubicación, frente o paquete contractual.

## Fuentes revisadas

- `programa_consolidado`: 14 proyectos con cronograma.
- `actividades`: 55 actividades existentes.
- `pdc`: 30 filas útiles.
- `general_informe_pdc`: 30 filas útiles.
- `docs/pdc/*.xlsx`: 8 archivos.

### Cronogramas en DB

| Proyecto | Filas | Actividades | Semanas | Fechas |
|---|---:|---:|---|---|
| Metrolinea Estación 2 | 18453 | 14448 | 1-26 | 2024-10-08 a 2026-01-02 |
| Optimización Aeropuerto JMC | 9339 | 7271 | 1-5 | 2026-04-24 a 2027-04-23 |
| Metrolinea Estación 1 | 8266 | 6240 | 1-28 | 2024-05-25 a 2025-03-28 |
| Metrolinea Estación 6 | 5818 | 4481 | 1-17 | 2024-05-25 a 2025-03-31 |
| Metrolinea Estación 16 - Edificio Descendente | 4347 | 3176 | 1-13 | 2025-08-21 a 2026-09-30 |
| Prueba | 1652 | 1085 | 1-7 | 2023-07-31 a 2024-02-22 |
| Milán Campestre Torre 19 | 920 | 795 | 1-5 | 2026-04-27 a 2026-09-20 |
| Metrolinea Estación 16 - Edificio Ascendente | 615 | 467 | 1-1 | 2025-08-15 a 2026-12-15 |
| Metrolinea Mampostería Estación 2 | 612 | 528 | 1-4 | 2026-01-27 a 2026-06-01 |
| Accesibilidad METRO 6260 | 396 | 396 | 1-3 | 2026-02-11 a 2026-08-30 |
| Da Porto | 273 | 242 | 1-1 | 2026-05-25 a 2028-02-22 |
| Metrolinea Confinamiento Estación 2 | 102 | 85 | 1-1 | 2025-11-19 a 2026-02-20 |
| Accesibilidad METRO 6259 | 80 | 62 | 1-1 | 2026-02-11 a 2026-08-17 |
| Aeropuerto Regional PC | 5 | 0 | 1-1 | 2026-07-01 a 2026-09-15 |

### Planes Excel

| Archivo | Hojas útiles | Filas extraídas |
|---|---|---:|
| 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026.xlsx | POR ACTIVIDAD, POR FECHAS, Programa LBV, Copia de CONTRATACION, POR ORDEN CRONOLOGICO | 1757 |
| 1. PLAN DE COMPRAS V2- SR.xlsx | POR ACTIVIDAD, POR FECHAS, Programa LBV, Copia de CONTRATACION, POR ORDEN CRONOLOGICO | 1514 |
| 20210208 PLAN DE COMPRAS (2).xlsx | fechas contratación, Copia de CONTRATACION, En orden cronológico | 177 |
| 20211228 PLAN DE COMPRAS CLÍNICA VETEZCO.xlsx | POR ACTIVIDAD, Copia de CONTRATACION, POR ORDEN CRONOLOGICO | 131 |
| 20220816 PLAN DE COMPRAS CRYSTA2.xlsx | POR ACTIVIDAD, Copia de CONTRATACION, POR ORDEN CRONOLOGICO | 252 |
| 20221205 PLAN DE COMPRAS MILAN CAMPESTRE.xlsx | Compras cronología (2), PLAN DE COMPRAS, Copia de CONTRATACION, Compras cronología | 83 |
| 20240401 PLAN DE COMPRAS.xlsx | fechas contratación, Copia de CONTRATACION, En orden cronológico | 184 |
| PLAN DE COMPRAS.xlsx | POR ACTIVIDAD, Copia de CONTRATACION, POR ORDEN CRONOLOGICO | 157 |

## Separación familia vs contrato

| Clasificación | Casos detectados | Uso esperado |
|---|---:|---|
| Familia operativa | 4865 | Puede alimentar Listado de Actividades si la evidencia es suficiente. |
| Elemento contractual | 1524 | Debe alimentar Contratos; no debe quedar listo como actividad. |

## Familias operativas candidatas

| Familia | Evidencia | Modalidades | Aliases/paquetes frecuentes | Ejemplos |
|---|---:|---|---|---|
| Estructura en Concreto | 9511 | Por definir; Suministro; 1; Mano de Obra; Mano de obra | Dados para columnas,; Columnas sótano a P1,; Armado y vaciado,; Fraguado, | Metrolinea Estación 1 · Dados para columnas, · Contexto: Placa cimentación, SOTANO, SECTOR 2; Metrolinea Estación 1 · Columnas sótano a P1, · Contexto: ESTRUCTURA EN CONCRETO, SECTOR 2 |
| Excavaciones y Movimiento de Tierra | 2040 | Por definir; 1 | Excavación general para plataforma de equipos,; Relleno en recebo cmoún para plataforma de soporte para maquinaria,; Excavación y relleno con mortero fluido para reemplazo de material contaminado,; Excavación etapa 1, | Metrolinea Estación 1 · Excavación general para plataforma de equipos, · Contexto: Adecuación zona para equipos, PANTALLAS, SECTOR 2; Metrolinea Estación 1 · Relleno en recebo cmoún para plataforma de soporte para maquinaria, · Contexto: Adecuación zona para equipos, PANTALLAS, SECTOR 2 |
| Preliminares de Obra | 1449 | Por definir; Equipos; 1; Mano de Obra; Suministro | Mantenimiento a cerramiento durante obra,; Cerramientos,; Demoliciones,; Cerramiento Tipo Persiana, | Prueba · Mantenimiento a cerramiento durante obra, · Contexto: Campamentos e Instalaciones, HOMECENTER CALI - PLAZA DE TOROS; Prueba · Cerramientos, · Contexto: Campamentos e Instalaciones, HOMECENTER CALI - PLAZA DE TOROS |
| Filtros, Tapas y Rejillas | 1344 | Por definir; Suministro; Mano de Obra | Rejillas ventilación Mezanine trastienda,; Filtros,; Suministro de rejillas,; Tramo 1 (inc. Instal. De rejillas) L=12.00 m, | Prueba · Rejillas ventilación Mezanine trastienda, · Contexto: Rejillas Interiores, HOMECENTER CALI - PLAZA DE TOROS; Optimización Aeropuerto JMC · Filtros, · Contexto: Area A-1, Franja A, Zona 1 (central - CIAN), (6) POSICIONES EN PLATAFORMA (FASE 1) |
| Carpinteria Metalica | 1167 | Suministro; Por definir; 2 | Suministro,; Instalación,; Puertas rejas Trastienda - Zona 1,; Estructura metálica, | Prueba · Suministro, · Contexto: Placa metálica para quipos HVAC., Estructuras y Plataformas, HOMECENTER CALI - PLAZA DE TOROS; Prueba · Instalación, · Contexto: Placa metálica para quipos HVAC., Estructuras y Plataformas, HOMECENTER CALI - PLAZA DE TOROS |
| Pisos Ceramicos, Porcelanatos, Gres y Concreto Pulido | 960 | Por definir; 1; Suministro | Zona 1,; Juntas Z1,; Zona 2,; Juntas Z2, | Prueba · Zona 1, · Contexto: Piso Industrial (dilatado, pulido, tratamiento de juntas), Pisos Industriales, HOMECENTER CALI - PLAZA DE TOROS; Prueba · Juntas Z1, · Contexto: Piso Industrial (dilatado, pulido, tratamiento de juntas), Pisos Industriales, HOMECENTER CALI - PLAZA DE TOROS |
| Red de Telecomunicaciones | 743 | Por definir; Suministro e instalación; Suministro; 2 | Elementos suplementarios,; Zona 1,; Zona 2,; Zona 3, | Prueba · Elementos suplementarios, · Contexto: Ducterias, cableado, accesorios y aparatos eléctricos, HOMECENTER CALI - PLAZA DE TOROS; Prueba · Zona 1, · Contexto: Equipos para Redes, Sistema de Datos, HOMECENTER CALI - PLAZA DE TOROS |
| Mamposteria en Ladrillo/Bloque Interior | 725 | Por definir; Suministro; 1 | Construcción muros exteriores,; Acabado interior muros exteriores,; Dinteles y Muros Tienda - M0,; Dinteles y Muros Mezanine - MZ, | Prueba · Construcción muros exteriores, · Contexto: Muros Exteriores, HOMECENTER CALI - PLAZA DE TOROS; Prueba · Acabado interior muros exteriores, · Contexto: Muros Exteriores, HOMECENTER CALI - PLAZA DE TOROS |
| Muros de Contencion | 501 | Por definir; Suministro; 1 | Alistamiento equipos,; Transporte equipos,; Puesta a punto equipos,; Pedido y figuración, | Metrolinea Estación 1 · Alistamiento equipos, · Contexto: PANTALLAS, SECTOR 2; Metrolinea Estación 1 · Transporte equipos, · Contexto: PANTALLAS, SECTOR 2 |
| Red Electrica | 455 | Por definir; Suministro; 1 | Rejillas Subestación,; Subestación,; Acometida,; Redes de distribución, | Prueba · Rejillas Subestación, · Contexto: Rejillas Interiores, HOMECENTER CALI - PLAZA DE TOROS; Prueba · Subestación, · Contexto: Sistemas Agente Limpio, Elementos Especiales, HOMECENTER CALI - PLAZA DE TOROS |
| Piloteaje y Micropilotes | 425 | Por definir; Equipos | Construcción de pilotes y otras obras (por terceros),; Recalce pilotes,; Corte y retiro de pilotes (POR ML1),; Pilotes , | Metrolinea Estación 1 · Construcción de pilotes y otras obras (por terceros), · Contexto: PRELIMINARES; Metrolinea Estación 6 · Construcción de pilotes y otras obras (por terceros), · Contexto: PRELIMINARES |
| Vigas de Cimentacion | 371 | Por definir | Solado,; Concreto Vigas,; Reemplazo grava tipo afirmado,; Vigas de cimentación, | Metrolinea Estación 2 · Solado, · Contexto: Vigas, SECTOR 2.1 EJES (AB-Q a AB-P"), Cimentación, SECTOR 2 EJES (AB-T a AB-P"), EDIFICIO ASCENDENTE 2, ESTRUCTURAS ASC; Metrolinea Estación 2 · Concreto Vigas, · Contexto: Vigas, SECTOR 2.1 EJES (AB-Q a AB-P"), Cimentación, SECTOR 2 EJES (AB-T a AB-P"), EDIFICIO ASCENDENTE 2, ESTRUCTURAS ASC |
| Cielos Rasos | 360 | Por definir; Suministro; 2 | Cielos Local,; Sala de Ventas - Mesanine Zona 1,; Enfermeria Mezanine Zona 1,; Centro Corte, | Prueba · Cielos Local, · Contexto: Cielos razos, HOMECENTER CALI - PLAZA DE TOROS; Prueba · Sala de Ventas - Mesanine Zona 1, · Contexto: Cielos razos, HOMECENTER CALI - PLAZA DE TOROS |
| Puertas y Accesorios | 357 | Por definir; Suministro; Suministro e instalación | Elementos suplementarios de fachada (Avisos y puertas Lam Galv),; Mezanine Trastienda y Trastienda (Puertas Fijas, deslizantes, cortinas) - Zona 1,; Puerta P5 y ajustes en zona de operador,; Instalación puerta PM-01 en nueva bodega, | Prueba · Elementos suplementarios de fachada (Avisos y puertas Lam Galv), · Contexto: Muros Exteriores, HOMECENTER CALI - PLAZA DE TOROS; Prueba · Mezanine Trastienda y Trastienda (Puertas Fijas, deslizantes, cortinas) - Zona 1, · Contexto: Puertas Interiores, HOMECENTER CALI - PLAZA DE TOROS |
| Pinturas Interiores y Exteriores | 351 | Por definir; 1 | Area mezanine y trastienda Zona 1,; General Tienda y area de Ventas Zona 2 y 3,; Area trastienda y Mezaninen Zona 1,; Pintura Demarcación, | Prueba · Area mezanine y trastienda Zona 1, · Contexto: Pintura Muros, Acabados Interiores, HOMECENTER CALI - PLAZA DE TOROS; Prueba · General Tienda y area de Ventas Zona 2 y 3, · Contexto: Piso en Pintura, Pisos, HOMECENTER CALI - PLAZA DE TOROS |
| Morteros de Nivelacion de Losas | 338 | Por definir; Mano de obra | Placa de contrapiso,; Concreto,; Corte diltaciones,; Fundida concreto, | Metrolinea Estación 1 · Placa de contrapiso, · Contexto: ESTRUCTURA EN CONCRETO, SECTOR 1; Metrolinea Estación 6 · Placa de contrapiso, · Contexto: ESTRUCTURA, SECTOR 2 |
| Ascensores | 331 | Por definir; 2 | Montaje estructura metálica ascensor externo N.O Madera (finalizada),; Acabados ascensor externo 2 N.OR Madera,; Instalación de ascensor externo 2 N.OR Madera,; Instalació de redes ascensor externo 2 N.OR Madera, | Accesibilidad METRO 6259 · Montaje estructura metálica ascensor externo N.O Madera (finalizada), · Contexto: Ascensor externo 2 N.OR Madera, Estación Madera; Accesibilidad METRO 6259 · Acabados ascensor externo 2 N.OR Madera, · Contexto: Ascensor externo 2 N.OR Madera, Estación Madera |
| Red Hidrosanitaria | 310 | Por definir; 1; Mano de Obra; Mano de obra | Equipo Presion Agua (2 Bombas),; Sistema de Agua Potable (Tuberias y Accesorios),; Instalació de redes ascensor interno 1 ORI Aguacatala,; Instalació de redes ascensor interno 2 OCC Aguacatala, | Prueba · Equipo Presion Agua (2 Bombas), · Contexto: Redes Hidrosanitarias, HOMECENTER CALI - PLAZA DE TOROS; Prueba · Sistema de Agua Potable (Tuberias y Accesorios), · Contexto: Redes Hidrosanitarias, HOMECENTER CALI - PLAZA DE TOROS |
| Revoques y Panetes | 286 | Por definir; 1; Mano de obra | Pobre y Pañete,; Revoques,; Revoque fachada exterior,; Revoques y llenos, | Metrolinea Estación 6 · Pobre y Pañete, · Contexto: Foso Escaleras T2 Eje 4B a 5B, Fosos Ascensores y Escaleras, ESTRUCTURA EN CONCRETO, SECTOR 1 (EJES 1 a 4 y 6 a10); Metrolinea Estación 6 · Pobre y Pañete, · Contexto: Foso Escaleras T2 Eje 4D a 5D, Fosos Ascensores y Escaleras, ESTRUCTURA EN CONCRETO, SECTOR 1 (EJES 1 a 4 y 6 a10) |
| Pisos y Enchapes | 281 | Por definir; 1; Suministro | Desmonte revestimiento muros, cielos y redes existentes,; Enchapes Ceramicos en Muros; Recubrimiento muros (estructura),; Recubrimiento muros (estructura y acabado), | Optimización Aeropuerto JMC · Desmonte revestimiento muros, cielos y redes existentes, · Contexto: Grupo 1 (8 puestos nuevos - 0 puestos actuales), Zona Internacional, (3) CHECK IN; Optimización Aeropuerto JMC · Recubrimiento muros (estructura), · Contexto: Grupo 1 (8 puestos nuevos - 0 puestos actuales), Zona Internacional, (3) CHECK IN |
| Zapatas de Cimentacion | 269 | Por definir | Excavación general y zapatas,; Fundida de nucleos y perimetral dado norte,; Concreto dado norte,; Concreto dado sur, | Metrolinea Estación 6 · Excavación general y zapatas, · Contexto: SOTANO, SECTOR 2; Metrolinea Estación 6 · Fundida de nucleos y perimetral dado norte, · Contexto: Zapatas, SOTANO, SECTOR 2 |
| Aseo de Apartamentos y Obra | 268 | Por definir | Aseo Final,; Consumo Agua y Energia,; Período 1,; Período 2, | Prueba · Aseo Final, · Contexto: Actividades de Aseo y Limpieza, HOMECENTER CALI - PLAZA DE TOROS; Prueba · Consumo Agua y Energia, · Contexto: Actividades de Aseo y Limpieza, HOMECENTER CALI - PLAZA DE TOROS |
| Aparatos Sanitarios | 267 | Por definir; Suministro; 1 | Aparatos Sanitarios Trastienda Mezanine Zona 1,; Sistema de Drenaje (Equipos, Tuberias y Drenajes),; Integración y automatización de control de sistema hidrosanitario ,; Cama, | Prueba · Aparatos Sanitarios Trastienda Mezanine Zona 1, · Contexto: Redes Hidrosanitarias, HOMECENTER CALI - PLAZA DE TOROS; Prueba · Sistema de Drenaje (Equipos, Tuberias y Drenajes), · Contexto: Sistema de alcantarillado Sanitario, HOMECENTER CALI - PLAZA DE TOROS |
| Red de Extinción | 256 | Por definir; Suministro; 2 | Local Comercial,; Red RCI; BHS NORTE,; L-1010, | Prueba · Local Comercial, · Contexto: Piso Laminado, Pisos, HOMECENTER CALI - PLAZA DE TOROS; Optimización Aeropuerto JMC · BHS NORTE, · Contexto: Zona internacional, Intervención de locales comerciales/zonas ocupadas por locatarios, (3) CHECK IN |
| Vias y Pavimentos | 234 | Por definir; 1; Suministro | Concreto,; Llegada de Acero (#6) dado viga viaducto Eje 3,; Llegada de Acero (#8) dado viga viaducto Eje 3,; Base granular, | Metrolinea Estación 1 · Concreto, · Contexto: Muros internos tanque Aguas Lluvias, SOTANO, SECTOR 2; Metrolinea Estación 6 · Llegada de Acero (#6) dado viga viaducto Eje 3, · Contexto: Losa aérea, Módulo 1.2.1 (Eje 3), Losa P3, ESTRUCTURA EN CONCRETO, SECTOR 1 (EJES 1 a 4 y 6 a10) |
| Pasamanos Tubulares y Cerrajeria | 233 | Por definir | Instalación de pisos en vinilo acabados y pasamanos puente interno Madera,; Instalación de pisos en vinilo acabados y pasamanos ,; Pasamanos,; Pasamanos escaleras, | Accesibilidad METRO 6259 · Instalación de pisos en vinilo acabados y pasamanos puente interno Madera, · Contexto: Puente interno Madera, Estación Madera; Accesibilidad METRO 6259 · Instalación de pisos en vinilo acabados y pasamanos puente interno Madera, · Contexto: Puente interno industriales, Estación Industriales |
| Torregrua | 218 | Suministro; Por definir; Equipos; 1 | Prealistamiento de torregrúas (diseño cimentación, adquisición de suministros, construcción cimentación y resistencia de; Montaje torregrúas,; Afectacion puesta en marcha torre grua,; Adjudicacion alquiler torre grua, | Metrolinea Estación 1 · Prealistamiento de torregrúas (diseño cimentación, adquisición de suministros, construcción cimentación y resistencia de · Contexto: PRELIMINARES; Metrolinea Estación 1 · Montaje torregrúas, · Contexto: PRELIMINARES |
| Ventaneria PVC y Aluminio | 166 | Por definir; Suministro | Ventaneria Local,; Puerta Ventana Local,; Ventaneria Linea Cajas - Zona 3,; Ventaneria Mezanine - Zona 1, | Prueba · Ventaneria Local, · Contexto: Ventaneria, HOMECENTER CALI - PLAZA DE TOROS; Prueba · Puerta Ventana Local, · Contexto: Ventaneria Interior, HOMECENTER CALI - PLAZA DE TOROS |
| Vidrieria | 120 | Por definir; Suministro | Fachadas arquitectonicas,; Fachada ampliación Madera,; Instalación de fachada puente interno Madera,; Fachada ampliación Industriales, | Prueba · Fachadas arquitectonicas, · Contexto: Muros Exteriores, HOMECENTER CALI - PLAZA DE TOROS; Accesibilidad METRO 6259 · Fachada ampliación Madera, · Contexto: Nivel 1 edificio de ampliación Madera, Estación Madera |
| Paisajismo | 120 | Por definir; Suministro; 2 | Roca hincada,; Colchón drenante + geotextil,; Subrasante mejorada,; Base asfáltica, | Optimización Aeropuerto JMC · Roca hincada, · Contexto: Area 2-1, Zona de nueva plataforma, Fase 2B (calle N - VERDE), Zona 2, (6) POSICIONES EN PLATAFORMA (FASE 1); Optimización Aeropuerto JMC · Colchón drenante + geotextil, · Contexto: Area 2-1, Zona de nueva plataforma, Fase 2B (calle N - VERDE), Zona 2, (6) POSICIONES EN PLATAFORMA (FASE 1) |
| Griferias e Incrustaciones | 100 | Por definir | Muebles, aparatos y griferias,; Instalación Muebles, aparatos y griferias,; Muebles, aparatos y griferias; APARATOS SANITARIOS + GRIFERIAS + ACCESORIOS | Optimización Aeropuerto JMC · Muebles, aparatos y griferias, · Contexto: Obra blanca, Bateria de baños, (5A) EQUIPAJE LLEGANDO (edificación nueva); Optimización Aeropuerto JMC · Instalación Muebles, aparatos y griferias, · Contexto: Obra blanca, Internacional Sala 9, Baños Salas de espera, (5B) ADECUACION SALA DE ESPERA ACTUAL |
| Aire Acondicionado Central | 91 | Por definir; Suministro; 2 | Conductores - Zona 1,; Baño conductores - Trastienda,; Zona 1,; Zona 2, | Prueba · Conductores - Zona 1, · Contexto: Escaleras, HOMECENTER CALI - PLAZA DE TOROS; Prueba · Baño conductores - Trastienda, · Contexto: Cielos razos, HOMECENTER CALI - PLAZA DE TOROS |
| Carpinteria en Madera | 83 | Por definir; 2 | Carpintería madera,; Instalación lavadero y mueble cocina Nivel 5; Instalación lavadero y mueble cocina Nivel 4; Instalación lavadero y mueble cocina Nivel 3 | Optimización Aeropuerto JMC · Carpintería madera, · Contexto: Obra civil y acabados, Zona Staff Airplan y FBO - Zona Cian, (5) SALA DE EMBARQUE REMOTA NACIONAL; Optimización Aeropuerto JMC · Carpintería madera, · Contexto: Zona pasillos y traslados, Zona Pasillo y traslados - Zona Morada, (5) SALA DE EMBARQUE REMOTA NACIONAL |
| Campamento de Obra | 73 | Por definir; Equipos; Mano de Obra; Suministro | Instalaciones Provisionales,; Campamentos y Aseo,; Entregables,; L-1040, 1041 Y 1043 - ALMACEN DE AIRPLAN…SE TRASLADAN PROVISIONALMENTE EN LA ESQUINA DE LA NAVIDAD (EJES 50 Y 51), | Prueba · Instalaciones Provisionales, · Contexto: Campamentos e Instalaciones, HOMECENTER CALI - PLAZA DE TOROS; Prueba · Campamentos y Aseo, · Contexto: Campamentos e Instalaciones, HOMECENTER CALI - PLAZA DE TOROS |
| Implementacion PMT | 65 | Por definir; Suministro; 2 | Afectación PMT ML1,; Afectación ML1 Cambio Diseño,; Suministro de Insumos e implementación,; Diseño de protecciones áreas a intervenir/aprobación por supervisión, | Metrolinea Estación 2 · Afectación PMT ML1, · Contexto: Losa Aerea, Módulo 4 Eje 5, Losa P2, ESTRUCTURA EN CONCRETO, ESTRUCTURA NAVE CENTRAL; Metrolinea Estación 2 · Afectación PMT ML1, · Contexto: Losa Aerea, Módulo 5 Eje 6, Losa P2, ESTRUCTURA EN CONCRETO, ESTRUCTURA NAVE CENTRAL |
| Cabinas de Bano | 57 | Por definir | Baño Hombres Mezanine Zona 1,; Baño Mujeres Mezanine Zona 1,; Retiro aparatos, muebles y divisiones baños oficinas Airplan,; Retiro aparatos, muebles y divisiones baños oficinas FBO, | Prueba · Baño Hombres Mezanine Zona 1, · Contexto: Accesorios y Divisiones de Baños, Actividades Adicionales, HOMECENTER CALI - PLAZA DE TOROS; Prueba · Baño Mujeres Mezanine Zona 1, · Contexto: Accesorios y Divisiones de Baños, Actividades Adicionales, HOMECENTER CALI - PLAZA DE TOROS |
| Impermeabilizaciones | 51 | Por definir; 1 | Impermeabilización muros,; Impermeabilización losa de cubierta,; Impermeabilización manto granillado; Impermeabilización de tanque inferior, | Metrolinea Estación 6 · Impermeabilización muros, · Contexto: SOTANO, SECTOR 2; Optimización Aeropuerto JMC · Impermeabilización losa de cubierta, · Contexto: Obra negra, Bateria de baños, (5A) EQUIPAJE LLEGANDO (edificación nueva) |
| Mobiliario Urbano | 35 | Por definir | Retiro divisiones, mobiliario, cielos y demolicines oficinas Airplan,; Retiro revestimiento, cielos, muebles, mobiliario, alfombra y pisos oficinas FBO,; Mobiliario no fijo,; RETIRO DE MOBILIARIO (COUNTER, PUESTOS DE TRABAJOS, DIVISIONES, LOCKERS, ALFOMBRAS) | Optimización Aeropuerto JMC · Retiro divisiones, mobiliario, cielos y demolicines oficinas Airplan, · Contexto: Demoliciones, Zona Staff Airplan y FBO - Zona Cian, (5) SALA DE EMBARQUE REMOTA NACIONAL; Optimización Aeropuerto JMC · Retiro revestimiento, cielos, muebles, mobiliario, alfombra y pisos oficinas FBO, · Contexto: Demoliciones, Zona Staff Airplan y FBO - Zona Cian, (5) SALA DE EMBARQUE REMOTA NACIONAL |
| Nomenclatura y Senalizacion | 29 | Suministro; Por definir | SUMINISTRO DE SEÑALIZACIÓN; DEMARCACIÓN HORIZONTAL; Señalización; SEÑALIZACION Y NUMERACION PARQUEADEROS | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC · SUMINISTRO DE SEÑALIZACIÓN · Contexto: 6 · 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026.xlsx; OPTIMIZACION TERMINAL 1 AEROPUERTO JMC · DEMARCACIÓN HORIZONTAL · Contexto: 6 · 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026.xlsx |
| Red de Gas | 28 | Por definir; 2 | Instalación Cerchas/Vigas Metálicas h:3.50 - 3.60,; RED DE GAS,; RED DE GAS; RED GAS | Metrolinea Confinamiento Estación 2 · Instalación Cerchas/Vigas Metálicas h:3.50 - 3.60, · Contexto: Cuartos Ejes 7 - 9 --- A-D´ (Zona 6), Nivel Intermedio, Nave Central, Estructura Confinamiento Muros Estación 2; Metrolinea Confinamiento Estación 2 · Instalación Cerchas/Vigas Metálicas h:3.50 - 3.60, · Contexto: Cuartos Ejes 7-9 (Zona 5), Nivel Intermedio, Nave Central, Estructura Confinamiento Muros Estación 2 |
| Malacate | 22 | Equipos; 1 | Poceta Alquiler de Herramientas,; MALACATE MIXTO PARA 1200 KG; ALQUILER CAMIONETA; MALACATE | Prueba · Poceta Alquiler de Herramientas, · Contexto: Elementos suplementarios particiones interiores, Muros Interiores, HOMECENTER CALI - PLAZA DE TOROS; Da Porto · MALACATE MIXTO PARA 1200 KG |
| Provisionales Electricos | 19 | Por definir; Mano de obra | Red energía provisional,; Provisional eléctrica,; Provisional eléctrica para obra,; PROVISIONALES ELÉCTRICAS | Optimización Aeropuerto JMC · Red energía provisional, · Contexto: Preliminares, desmontes y demoliciones, (3B) FILTRO DE SEGUIRDAD INTERNACIONAL; Optimización Aeropuerto JMC · Provisional eléctrica, · Contexto: Preliminares, desmontes y demoliciones, (7) NUEVO CENTRO DE CONEXIONES |
| Mesones de Bano | 15 | Suministro; Por definir | SUMINISTRO DE MESONES DE BAÑO; MESONES QUARZTONE BAÑOS; MESON Y MUEBLE BAÑOS; SUMINISTRO DE MESONES DE BAÑO | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC · SUMINISTRO DE MESONES DE BAÑO · Contexto: 5 · 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026.xlsx; OPTIMIZACION TERMINAL 1 AEROPUERTO JMC · SUMINISTRO DE MESONES DE BAÑO · Contexto: 5A · 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026.xlsx |
| Vigilancia | 14 | Por definir; 2 | Vallas/señales/seguridad obra 12500 - 15000m2,; VIGILANCIA; VIGILANCIA (3 FRENTES DE VIGILANCIA); SERVICIO VIGILANCIA | Prueba · Vallas/señales/seguridad obra 12500 - 15000m2, · Contexto: Seguridad Obra, HOMECENTER CALI - PLAZA DE TOROS; Da Porto · VIGILANCIA |
| Banos Portatiles | 14 | Suministro; Por definir | BAÑOS PORTÁTILES PRELIMINARES; BAÑOS PORTATILES; BAÑOS PORTÁTILES; BAÑOS PORTÁTILES | Da Porto · BAÑOS PORTÁTILES PRELIMINARES; OPTIMIZACION TERMINAL 1 AEROPUERTO JMC · BAÑOS PORTATILES · Contexto: Peliminares · 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026.xlsx |

## Elementos contractuales detectados

| Elemento | Evidencia | Paquetes frecuentes | Uso esperado |
|---|---:|---|---|
| Encofrado y Obra Falsa | 5661 | Desencofrado,; Encofrado nudos,; Obra falsa,; Encofrado, | Alimenta `/contratos/`, no `/listado-actividades/`. |
| Acero de Refuerzo y Estructural | 3651 | Instalación malla a tierra,; Acero vigas y nervios,; Malla ,; Acero, | Alimenta `/contratos/`, no `/listado-actividades/`. |
| Aligerantes Perdidos y Recuperables | 1655 | Aligerante,; Aligerante Losa Aerea,; Aligerante (A-B),; Aligerante (B-C), | Alimenta `/contratos/`, no `/listado-actividades/`. |
| Mano de Obra - Cimentacion | 1214 | Solado,; Concreto,; Fundida de Nucleos,; Concreto Dados, | Alimenta `/contratos/`, no `/listado-actividades/`. |
| Losas de Cimentacion | 772 | Capa de rajón,; Solado,; Aligeramiento,; Concreto, | Alimenta `/contratos/`, no `/listado-actividades/`. |
| Mano de Obra - Estructura | 519 | Solado,; Concreto,; Fundida,; Pases, | Alimenta `/contratos/`, no `/listado-actividades/`. |
| Luminarias y Artefactos Electricos | 399 | Alambrado,; Aparateado,; Iluminación Emergencia,; Integración y automatización de control de sistema de iluminación , | Alimenta `/contratos/`, no `/listado-actividades/`. |
| Fachada HPL, Vidrio y Aluminio | 396 | Estructura y cubierta ampliación Madera,; Instalación de cubierta puente intero Madera,; Estructura y cubierta ampliación Industriales,; Instalación de cubierta puente intero Ayurá, | Alimenta `/contratos/`, no `/listado-actividades/`. |
| Estuco | 107 | Estucos,; Estuco,; Estucos; Estuco | Alimenta `/contratos/`, no `/listado-actividades/`. |
| Equipos de Extincion | 79 | Zona 1,; Zona 2,; Zona 3,; Gabinete para 12 Modulos c/Bastidor (Norma CE), | Alimenta `/contratos/`, no `/listado-actividades/`. |
| Mano de Obra - Acabados | 53 | MANO DE OBRA REVOQUE; MANO DE OBRA ENCHAPES; MANO DE OBRA PISOS; MANO DE OBRA REVOQUE FACHADA | Alimenta `/contratos/`, no `/listado-actividades/`. |
| Mano de Obra - Mamposteria | 45 | Demolición de mampostería, baños y desmonte vidirera interna,; RETIROS Y DEMOLICIONES (Puertas, Barandas, Cielos, Mampostería, pisos, estanterías, vidrieras); MANO DE OBRA MAMPOSTERÍA; Demolición de mampostería, baños y vidirera interna | Alimenta `/contratos/`, no `/listado-actividades/`. |
| Contenedores | 9 | CONTENEDORES OFICINA Y ALMACEN; CONTENEDORES; CONTENEDORES; CONTENEDORES OFICINA Y ALMACEN | Alimenta `/contratos/`, no `/listado-actividades/`. |
| Geodren | 3 | GEODREN PLANAR; GEODREN; GEODREN | Alimenta `/contratos/`, no `/listado-actividades/`. |
| Mano de Obra - Excavaciones | 2 | MOVIMIENTO DE TIERRA EXCAVACIONES Y LLENOS; MOVIMIENTO DE TIERRA | Alimenta `/contratos/`, no `/listado-actividades/`. |
| Mano de Obra - Urbanismo | 1 | MANO DE OBRA URBANISMO; MANO DE OBRA URBANISMO | Alimenta `/contratos/`, no `/listado-actividades/`. |

## Asociaciones familia → paquete/modalidad

| Familia | Paquete / contrato | Modalidades | Fuentes | Proyectos | Evidencia |
|---|---|---|---|---|---:|
| Luminarias y Artefactos Electricos | ILUMINACIÓN | Por definir | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC; 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 83 |
| Red de Telecomunicaciones | SEGURIDAD Y CONTROL | Por definir | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC; 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; EL ROSAL | 62 |
| Pinturas Interiores y Exteriores | Pintura | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; EL ROSAL; 1. PLAN DE COMPRAS V2- SR | 54 |
| Red Electrica | INSTALACIONES ELÉCTRICAS | Por definir | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC; EL ROSAL; OPTIMIZACION TERMINAL 1 JMC | 50 |
| Estuco | Estuco | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; EL ROSAL; 1. PLAN DE COMPRAS V2- SR | 50 |
| Mamposteria en Ladrillo/Bloque Interior | Mampostería | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; OPTIMIZACION TERMINAL 1 JMC; DA PORTO | 41 |
| Cielos Rasos | Cielos | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; DA PORTO; 1. PLAN DE COMPRAS V2- SR | 41 |
| Aseo de Apartamentos y Obra | Aseo y entrega | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 40 |
| Aseo de Apartamentos y Obra | ASEO FINAL Y ENTREGA | Por definir | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC | 36 |
| Carpinteria Metalica | ESTRUCTURA METÁLICA | Por definir | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC; 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 30 |
| Red Electrica | Red electrica | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; DA PORTO; 1. PLAN DE COMPRAS V2- SR | 29 |
| Acero de Refuerzo y Estructural | ACERO DE REFUERZO | Suministro; Por definir | pdc; general_informe_pdc; excel_pdc | Prueba; Da Porto; EL ROSAL | 28 |
| Revoques y Panetes | Revoques y llenos | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 28 |
| Preliminares de Obra | CERRAMIENTOS PROVISIONALES EN SUPER BOARD | Por definir | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC | 27 |
| Vias y Pavimentos | MUROS LIVIANOS Y CIELOS | Por definir | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC | 27 |
| Pinturas Interiores y Exteriores | PINTURA SOBRE MUROS Y CIELOS | Por definir | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC | 27 |
| Estructura en Concreto | MANO DE OBRA ESTRUCTURA EN CONCRETO | Mano de Obra; Mano de obra | pdc; general_informe_pdc; excel_pdc | Da Porto; OPTIMIZACION TERMINAL 1 AEROPUERTO JMC | 26 |
| Red Hidrosanitaria | Redes hidrosanitarias | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; DA PORTO; 1. PLAN DE COMPRAS V2- SR | 25 |
| Acero de Refuerzo y Estructural | SUMINISTRO DE ACERO | Suministro | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC | 24 |
| Ventaneria PVC y Aluminio | VENTANERÍA Y PUERTAS VIDRIERAS | Por definir | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC | 24 |
| Morteros de Nivelacion de Losas | Morteros | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 24 |
| Red Electrica | Redes Electricas | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR; CAMINO VERDE | 24 |
| Preliminares de Obra | Demoliciones | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR; CLÍNICA VETERINARIA VETEZCO | 24 |
| Mano de Obra - Acabados | MANO DE OBRA REVOQUE | Mano de obra | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC; CAMINO VERDE; 20210208 PLAN DE COMPRAS (2) | 23 |
| Preliminares de Obra | Preliminares, desmontes y demoliciones | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 22 |
| Pisos y Enchapes | Enchapes en muros | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 22 |
| Griferias e Incrustaciones | Muebles, aparatos y griferias | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 22 |
| Vidrieria | Fachada baños | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 22 |
| Pisos Ceramicos, Porcelanatos, Gres y Concreto Pulido | PISO EN GRANO (ROCA) | Por definir | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC | 21 |
| Mano de Obra - Mamposteria | MANO DE OBRA MAMPOSTERÍA | Mano de obra | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC; EL ROSAL | 20 |
| Carpinteria Metalica | CARPINTERÍA METÁLICA | Por definir | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC; 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; DA PORTO | 20 |
| Pisos Ceramicos, Porcelanatos, Gres y Concreto Pulido | PISOS | Por definir | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC; 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 20 |
| Mano de Obra - Mamposteria | RETIROS Y DEMOLICIONES (Puertas, Barandas, Cielos, Mampostería, pisos, estanterías, vidrieras) | Por definir | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC | 18 |
| Puertas y Accesorios | SUMINISTRO E INSTALACIÓN DE PUERTAS | Suministro e instalación | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC | 18 |
| Excavaciones y Movimiento de Tierra | Excavación | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 18 |
| Red de Extinción | Intervención de locales comerciales/zonas ocupadas por locatarios | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 18 |
| Implementacion PMT | TOPOGRAFIA | Por definir | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC; EL ROSAL; OPTIMIZACION TERMINAL 1 JMC | 17 |
| Mano de Obra - Acabados | MANO DE OBRA ENCHAPES | Mano de obra | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC; EL ROSAL | 17 |
| Torregrua | TORREGRÚA | Por definir | excel_pdc | EL ROSAL; OPTIMIZACION TERMINAL 1 JMC; CAMINO VERDE | 16 |
| Banos Portatiles | BAÑOS PORTÁTILES | Suministro; Por definir | pdc; general_informe_pdc; excel_pdc | Da Porto; OPTIMIZACION TERMINAL 1 AEROPUERTO JMC; EL ROSAL | 14 |
| Red Hidrosanitaria | INSTALACIONES HIDROSANITARIAS | Por definir | excel_pdc | EL ROSAL; OPTIMIZACION TERMINAL 1 JMC; CRISTA | 14 |
| Excavaciones y Movimiento de Tierra | EXCAVACIONES Y LLENOS | Por definir | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC; DA PORTO | 13 |
| Impermeabilizaciones | IMPERMEABILIZACIONES | Por definir | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC; EL ROSAL; HOTEL SAN FRANCISCO | 13 |
| Filtros, Tapas y Rejillas | Filtros | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; DA PORTO; 1. PLAN DE COMPRAS V2- SR | 13 |
| Preliminares de Obra | RETIROS Y DEMOLICIONES | Por definir | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC | 12 |
| Acero de Refuerzo y Estructural | SUMINISTRO METALDECK | Suministro | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC | 12 |
| Red Hidrosanitaria | MANO DE OBRA INSTALACIONES HIDROSANITARIAS | Mano de obra | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC | 12 |
| Aparatos Sanitarios | SUMINISTRO DE TUBERÍAS Y ACCESORIOS HIDROSANITARIOS | Suministro | excel_pdc | OPTIMIZACION TERMINAL 1 AEROPUERTO JMC | 12 |
| Vias y Pavimentos | Sub-base granular | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 12 |
| Vias y Pavimentos | Base granular | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 12 |
| Red de Telecomunicaciones | Voz y datos | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 12 |
| Preliminares de Obra | Cerramiento y protecciones | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 12 |
| Pisos y Enchapes | Desmonte revestimiento muros, cielos y redes existentes | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 12 |
| Muros de Contencion | Redes en cielos Nivel 2 (para luminarias y pantallas) | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 12 |
| Cielos Rasos | Instalación cielos (tipo bafle) | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 12 |
| Pisos Ceramicos, Porcelanatos, Gres y Concreto Pulido | Núcleos de piso | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 12 |
| Carpinteria Metalica | Instalación Muebles Counters | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 12 |
| Muros de Contencion | Pantallas | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 12 |
| Red de Telecomunicaciones | Conexión eléctrica,voz y datos e iluminación | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 12 |
| Mamposteria en Ladrillo/Bloque Interior | Redes por muros | Por definir | excel_pdc | 1. PLAN DE COMPRAS JMC T1 V2 20250703 - VERSION 29 MAYO 2026; 1. PLAN DE COMPRAS V2- SR | 12 |

## Patrones de confusión detectados

### Carpintería/revestimiento expresado como eje

- Casos detectados: 4.
- Optimización Aeropuerto JMC: `Ejes 45 y 47,` → familia candidata `Paisajismo`; contexto `Revestimiento fórmica columnas, Carpinterías, Zona Banda 5 - Zona Verde, (5) SALA DE EMBARQUE REMOTA NACIONAL`.
- Optimización Aeropuerto JMC: `Eje 48,` → familia candidata `Paisajismo`; contexto `Revestimiento fórmica columnas, Carpinterías, Zona Banda 5 - Zona Verde, (5) SALA DE EMBARQUE REMOTA NACIONAL`.
- Optimización Aeropuerto JMC: `Eje 49,` → familia candidata `Paisajismo`; contexto `Revestimiento fórmica columnas, Carpinterías, Zona Banda 5 - Zona Verde, (5) SALA DE EMBARQUE REMOTA NACIONAL`.
- Optimización Aeropuerto JMC: `Eje 50,` → familia candidata `Paisajismo`; contexto `Revestimiento fórmica columnas, Carpinterías, Zona Banda 5 - Zona Verde, (5) SALA DE EMBARQUE REMOTA NACIONAL`.

### Excavación bajo capítulo de estructura

- Casos detectados: 50.
- Metrolinea Estación 6: `Excavación,` → familia candidata `Excavaciones y Movimiento de Tierra`; contexto `Tubería, Sector 2, Obras de construccion sistemas hidrosanitarios, ESTRUCTURA EN CONCRETO, SECTOR 1`.
- Metrolinea Estación 6: `Excavación,` → familia candidata `Excavaciones y Movimiento de Tierra`; contexto `Cajas, Sector 2, Obras de construccion sistemas hidrosanitarios, ESTRUCTURA EN CONCRETO, SECTOR 1`.
- Metrolinea Estación 6: `Excavación,` → familia candidata `Excavaciones y Movimiento de Tierra`; contexto `Tubería, Módulo 1.2.1, Obras de construccion sistemas hidrosanitarios, ESTRUCTURA EN CONCRETO, SECTOR 1`.
- Metrolinea Estación 6: `Excavación,` → familia candidata `Excavaciones y Movimiento de Tierra`; contexto `Cajas, Módulo 1.2.1, Obras de construccion sistemas hidrosanitarios, ESTRUCTURA EN CONCRETO, SECTOR 1`.
- Metrolinea Estación 6: `Excavación,` → familia candidata `Excavaciones y Movimiento de Tierra`; contexto `Tubería, Módulo 1.2.2, Obras de construccion sistemas hidrosanitarios, ESTRUCTURA EN CONCRETO, SECTOR 1`.

### Extinción con redes/tubería debe revisarse como RCI

- Casos detectados: 3.
- Optimización Aeropuerto JMC: `Instalación tubería y accesorios FBO/Staff,` → familia candidata `Equipos de Extincion`; contexto `Extinción, Redes, (5) SALA DE EMBARQUE REMOTA NACIONAL`.
- Optimización Aeropuerto JMC: `Instalación tubería y accesorios zona pasillo,` → familia candidata `Equipos de Extincion`; contexto `Extinción, Redes, (5) SALA DE EMBARQUE REMOTA NACIONAL`.
- Optimización Aeropuerto JMC: `Instalación tubería y accesorios zona bandas,` → familia candidata `Equipos de Extincion`; contexto `Extinción, Redes, (5) SALA DE EMBARQUE REMOTA NACIONAL`.

### Familia inferida solo por contexto/capítulo

- Casos detectados: 1558.
- Prueba: `Instalaciones Provisionales,` → familia candidata `Campamento de Obra`; contexto `Campamentos e Instalaciones, HOMECENTER CALI - PLAZA DE TOROS`.
- Prueba: `Entregables,` → familia candidata `Campamento de Obra`; contexto `Campamentos e Instalaciones, HOMECENTER CALI - PLAZA DE TOROS`.
- Prueba: `Zona 1,` → familia candidata `Pisos Ceramicos, Porcelanatos, Gres y Concreto Pulido`; contexto `Piso Industrial (dilatado, pulido, tratamiento de juntas), Pisos Industriales, HOMECENTER CALI - PLAZA DE TOROS`.
- Prueba: `Juntas Z1,` → familia candidata `Pisos Ceramicos, Porcelanatos, Gres y Concreto Pulido`; contexto `Piso Industrial (dilatado, pulido, tratamiento de juntas), Pisos Industriales, HOMECENTER CALI - PLAZA DE TOROS`.
- Prueba: `Zona 2,` → familia candidata `Pisos Ceramicos, Porcelanatos, Gres y Concreto Pulido`; contexto `Piso Industrial (dilatado, pulido, tratamiento de juntas), Pisos Industriales, HOMECENTER CALI - PLAZA DE TOROS`.

### Mezcla red con equipo/accesorio

- Casos detectados: 32.
- Prueba: `Equipo Presion Agua (2 Bombas),` → familia candidata `Red Hidrosanitaria`; contexto `Redes Hidrosanitarias, HOMECENTER CALI - PLAZA DE TOROS`.
- Prueba: `Sistema de Agua Potable (Tuberias y Accesorios),` → familia candidata `Red Hidrosanitaria`; contexto `Redes Hidrosanitarias, HOMECENTER CALI - PLAZA DE TOROS`.
- Prueba: `Aparatos y tablero,` → familia candidata `Red Electrica`; contexto `Ducterias, cableado, accesorios y aparatos eléctricos, HOMECENTER CALI - PLAZA DE TOROS`.
- Optimización Aeropuerto JMC: `Conexión de equipos,` → familia candidata `Red Electrica`; contexto `Red electrica, Redes, (3B) FILTRO DE SEGUIRDAD INTERNACIONAL`.
- Optimización Aeropuerto JMC: `Conexión de equipos,` → familia candidata `Red de Telecomunicaciones`; contexto `Red voz y datos, seguridad y control y detección, Redes, (3B) FILTRO DE SEGUIRDAD INTERNACIONAL`.

### Revisar separación excavación vs cimentación/cárcamo

- Casos detectados: 126.
- Metrolinea Estación 1: `Excavación,` → familia candidata `Excavaciones y Movimiento de Tierra`; contexto `Módulo 1.1, CIMENTACION, SECTOR 1`.
- Metrolinea Estación 1: `Excavación,` → familia candidata `Excavaciones y Movimiento de Tierra`; contexto `Módulo 1.2, CIMENTACION, SECTOR 1`.
- Metrolinea Estación 1: `Excavación,` → familia candidata `Excavaciones y Movimiento de Tierra`; contexto `Módulo 1.3, CIMENTACION, SECTOR 1`.
- Metrolinea Estación 1: `Excavación,` → familia candidata `Excavaciones y Movimiento de Tierra`; contexto `Módulo 1.4, CIMENTACION, SECTOR 1`.
- Metrolinea Estación 1: `Excavación,` → familia candidata `Excavaciones y Movimiento de Tierra`; contexto `Módulo 1.5, CIMENTACION, SECTOR 1`.

### Seguridad/control confundible con incendio

- Casos detectados: 6.
- Optimización Aeropuerto JMC: `Conexión de equipos,` → familia candidata `Red de Telecomunicaciones`; contexto `Red voz y datos, seguridad y control y detección, Redes, (3B) FILTRO DE SEGUIRDAD INTERNACIONAL`.
- Optimización Aeropuerto JMC: `Gabinete para 12 Modulos c/Bastidor (Norma CE),` → familia candidata `Equipos de Extincion`; contexto `Suministro equipos redes: telecomunicación, seguridad y control, detección, Redes, (3) CHECK IN`.
- Optimización Aeropuerto JMC: `Equipos y gabinetes,` → familia candidata `Equipos de Extincion`; contexto `Suministros, Seguridad y control, Redes, (5A) EQUIPAJE LLEGANDO (edificación nueva)`.
- Optimización Aeropuerto JMC: `Instalación equipos,` → familia candidata `Red de Telecomunicaciones`; contexto `Seguridad y control, Redes, (5A) EQUIPAJE LLEGANDO (edificación nueva)`.
- Optimización Aeropuerto JMC: `Suiches (equipos del capítulo de pptos),` → familia candidata `Red de Telecomunicaciones`; contexto `Seguridad y control, Suministros, Redes, (5) SALA DE EMBARQUE REMOTA NACIONAL`.

### Ubicación usada como actividad

- Casos detectados: 431.
- Prueba: `Zona 1,` → familia candidata `Pisos Ceramicos, Porcelanatos, Gres y Concreto Pulido`; contexto `Piso Industrial (dilatado, pulido, tratamiento de juntas), Pisos Industriales, HOMECENTER CALI - PLAZA DE TOROS`.
- Prueba: `Zona 2,` → familia candidata `Pisos Ceramicos, Porcelanatos, Gres y Concreto Pulido`; contexto `Piso Industrial (dilatado, pulido, tratamiento de juntas), Pisos Industriales, HOMECENTER CALI - PLAZA DE TOROS`.
- Prueba: `Zona 3,` → familia candidata `Pisos Ceramicos, Porcelanatos, Gres y Concreto Pulido`; contexto `Piso Industrial (dilatado, pulido, tratamiento de juntas), Pisos Industriales, HOMECENTER CALI - PLAZA DE TOROS`.
- Prueba: `Area mezanine y trastienda Zona 1,` → familia candidata `Pinturas Interiores y Exteriores`; contexto `Pintura Muros, Acabados Interiores, HOMECENTER CALI - PLAZA DE TOROS`.
- Prueba: `Area Trastienda Zona 1,` → familia candidata `Pisos Laminados`; contexto `Piso Laminado, Pisos, HOMECENTER CALI - PLAZA DE TOROS`.

## Muestras para validación manual

### JMC

- Preliminares de Obra: Optimización Aeropuerto JMC · Movilización general (entrada de insumos y equipos), · Contexto: Preliminares, desmontes y demoliciones, (6) POSICIONES EN PLATAFORMA (FASE 1)
- Preliminares de Obra: Optimización Aeropuerto JMC · Instalaciones provisionales satélite (oficinas y cerramientos), · Contexto: Preliminares, desmontes y demoliciones, (6) POSICIONES EN PLATAFORMA (FASE 1)
- Preliminares de Obra: Optimización Aeropuerto JMC · Replanteo de redes existentes, · Contexto: Movimiento de redes de taxeo y otras, (6) POSICIONES EN PLATAFORMA (FASE 1)
- Preliminares de Obra: Optimización Aeropuerto JMC · Retiro y by-pass en redes (Manuel Torres), · Contexto: Movimiento de redes de taxeo y otras, (6) POSICIONES EN PLATAFORMA (FASE 1)
- Excavaciones y Movimiento de Tierra: Optimización Aeropuerto JMC · Descapote, · Contexto: Zona 1 (central - CIAN), (6) POSICIONES EN PLATAFORMA (FASE 1)
- Excavaciones y Movimiento de Tierra: Optimización Aeropuerto JMC · Excavación, · Contexto: Area A-1, Franja A, Zona 1 (central - CIAN), (6) POSICIONES EN PLATAFORMA (FASE 1)
- Filtros, Tapas y Rejillas: Optimización Aeropuerto JMC · Filtros, · Contexto: Area A-1, Franja A, Zona 1 (central - CIAN), (6) POSICIONES EN PLATAFORMA (FASE 1)
- Vias y Pavimentos: Optimización Aeropuerto JMC · Base granular, · Contexto: Area A-1, Franja A, Zona 1 (central - CIAN), (6) POSICIONES EN PLATAFORMA (FASE 1)
- Excavaciones y Movimiento de Tierra: Optimización Aeropuerto JMC · Excavación, · Contexto: Area A-2, Franja A, Zona 1 (central - CIAN), (6) POSICIONES EN PLATAFORMA (FASE 1)
- Filtros, Tapas y Rejillas: Optimización Aeropuerto JMC · Filtros, · Contexto: Area A-2, Franja A, Zona 1 (central - CIAN), (6) POSICIONES EN PLATAFORMA (FASE 1)
- Vias y Pavimentos: Optimización Aeropuerto JMC · Base granular, · Contexto: Area A-2, Franja A, Zona 1 (central - CIAN), (6) POSICIONES EN PLATAFORMA (FASE 1)
- Excavaciones y Movimiento de Tierra: Optimización Aeropuerto JMC · Excavación, · Contexto: Area A-3, Franja A, Zona 1 (central - CIAN), (6) POSICIONES EN PLATAFORMA (FASE 1)

### DA PORTO

- Preliminares de Obra: Da Porto · LOCALIZACIÓN Y REPLANTEO, · Contexto: PRELIMINARES, DAPORTO TORRE 3
- Campamento de Obra: Da Porto · CONSTRUCCIÓN DE CAMPAMENTOS, · Contexto: PRELIMINARES, DAPORTO TORRE 3
- Excavaciones y Movimiento de Tierra: Da Porto · EXCAVACIÓN A COTA 2110, · Contexto: MOVIMIENTO DE TIERRA, DAPORTO TORRE 3
- Piloteaje y Micropilotes: Da Porto · MICROPILOTES INSERTOS, · Contexto: ESTRUCTURA, DAPORTO TORRE 3
- Losas de Cimentacion: Da Porto · LOSA DE CIMENTACIÓN SÓTANO 3, · Contexto: ESTRUCTURA, DAPORTO TORRE 3
- Estructura en Concreto: Da Porto · COLUMNAS SÓTANO 3, · Contexto: ESTRUCTURA, DAPORTO TORRE 3
- Estructura en Concreto: Da Porto · COLUMNAS SÓTANO 2, · Contexto: ESTRUCTURA, DAPORTO TORRE 3
- Estructura en Concreto: Da Porto · COLUMNAS SÓTANO 1, · Contexto: ESTRUCTURA, DAPORTO TORRE 3
- Pisos Ceramicos, Porcelanatos, Gres y Concreto Pulido: Da Porto · LOSA AÉREA PISO 1, · Contexto: ESTRUCTURA, DAPORTO TORRE 3
- Estructura en Concreto: Da Porto · COLUMNAS PISO 1, · Contexto: ESTRUCTURA, DAPORTO TORRE 3
- Pisos Ceramicos, Porcelanatos, Gres y Concreto Pulido: Da Porto · LOSA AÉREA PISO 2, · Contexto: ESTRUCTURA, DAPORTO TORRE 3
- Estructura en Concreto: Da Porto · COLUMNAS PISO 2, · Contexto: ESTRUCTURA, DAPORTO TORRE 3

### MILAN

- Fachada HPL, Vidrio y Aluminio: Milán Campestre Torre 19 · Antepechos cubierta
- Morteros de Nivelacion de Losas: Milán Campestre Torre 19 · Losa de contrapiso Nivel 95
- Mamposteria en Ladrillo/Bloque Interior: Milán Campestre Torre 19 · Mampostería nivel 95
- Mamposteria en Ladrillo/Bloque Interior: Milán Campestre Torre 19 · Mampostería nivel 5
- Mamposteria en Ladrillo/Bloque Interior: Milán Campestre Torre 19 · Mampostería nivel 4
- Mamposteria en Ladrillo/Bloque Interior: Milán Campestre Torre 19 · Mampostería nivel 3
- Mamposteria en Ladrillo/Bloque Interior: Milán Campestre Torre 19 · Mampostería nivel 2
- Mamposteria en Ladrillo/Bloque Interior: Milán Campestre Torre 19 · Mampostería nivel 1
- Mamposteria en Ladrillo/Bloque Interior: Milán Campestre Torre 19 · Mampostería nivel 99
- Mamposteria en Ladrillo/Bloque Interior: Milán Campestre Torre 19 · Mampostería nivel 98
- Mamposteria en Ladrillo/Bloque Interior: Milán Campestre Torre 19 · Mampostería nivel 97
- Mamposteria en Ladrillo/Bloque Interior: Milán Campestre Torre 19 · Mampostería nivel 96

### METROLINEA

- Preliminares de Obra: Metrolinea Estación 1 · Recepción área de proyecto (con cerramiento), · Contexto: MOVILIZACION
- Piloteaje y Micropilotes: Metrolinea Estación 1 · Construcción de pilotes y otras obras (por terceros), · Contexto: PRELIMINARES
- Preliminares de Obra: Metrolinea Estación 1 · Localización y replanteo, · Contexto: PRELIMINARES
- Torregrua: Metrolinea Estación 1 · Prealistamiento de torregrúas (diseño cimentación, adquisición de suministros, construcción cimentación y resistencia de · Contexto: PRELIMINARES
- Torregrua: Metrolinea Estación 1 · Montaje torregrúas, · Contexto: PRELIMINARES
- Muros de Contencion: Metrolinea Estación 1 · Alistamiento equipos, · Contexto: PANTALLAS, SECTOR 2
- Muros de Contencion: Metrolinea Estación 1 · Transporte equipos, · Contexto: PANTALLAS, SECTOR 2
- Muros de Contencion: Metrolinea Estación 1 · Puesta a punto equipos, · Contexto: PANTALLAS, SECTOR 2
- Excavaciones y Movimiento de Tierra: Metrolinea Estación 1 · Excavación general para plataforma de equipos, · Contexto: Adecuación zona para equipos, PANTALLAS, SECTOR 2
- Excavaciones y Movimiento de Tierra: Metrolinea Estación 1 · Relleno en recebo cmoún para plataforma de soporte para maquinaria, · Contexto: Adecuación zona para equipos, PANTALLAS, SECTOR 2
- Excavaciones y Movimiento de Tierra: Metrolinea Estación 1 · Excavación y relleno con mortero fluido para reemplazo de material contaminado, · Contexto: Viga guía, PANTALLAS, SECTOR 2
- Muros de Contencion: Metrolinea Estación 1 · Pedido y figuración, · Contexto: Acero, Viga guía, PANTALLAS, SECTOR 2

## Reglas de uso del corpus

- Usar estos datos para proponer familias, aliases y asociaciones contractuales.
- No convertir paquetes de compra en actividades operativas sin validar el nombre real de programa.
- Si una familia aparece solo por capítulo, debe quedar por revisar.
- Si la hoja del cronograma es solo ubicación, usar ancestro operativo claro o pedir revisión.

## Comando de regeneración

```bash
docker compose exec app php docs/qa/pdc_family_corpus_extractor.php
docker compose exec app php docs/qa/pdc_family_corpus_extractor.php --verify
```
