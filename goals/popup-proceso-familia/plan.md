# Plan: Popup de Proceso para Familias Auto-generadas

## Objetivo
Agregar un ícono de info-circle en la columna de acciones de cada familia auto-generada que, al hacer clic, muestre un popover/tooltip con el proceso de cómo se llegó a esa familia.

## Archivos a modificar

### 1. Backend: ListadoActividadesApiController.php
**Ubicación:** `src/Controllers/Api/ListadoActividadesApiController.php`

**Cambios:**
- Modificar el método `list()` para incluir dos campos adicionales en cada registro:
  - `auto_generado` (boolean): `true` si la familia tiene registros en `actividad_programa_fuentes`
  - `fuentes_info` (object|null): Datos de la tabla `actividad_programa_fuentes` agrupados

**Query SQL a agregar (subquery o JOIN):**
```sql
SELECT 
    CASE WHEN EXISTS(
        SELECT 1 FROM actividad_programa_fuentes 
        WHERE project_id = a.project_id 
        AND actividad_id = a.Id 
        AND semana = a.semanaActualizacion
    ) THEN 1 ELSE 0 END AS auto_generado
```

**Para fuentes_info:**
```sql
SELECT JSON_ARRAYAGG(
    JSON_OBJECT(
        'actividad', apf.source_activity,
        'fecha', apf.source_start_date,
        'regla', apf.match_rule,
        'confianza', apf.confidence,
        'contexto', apf.context
    )
) AS fuentes_info
FROM actividad_programa_fuentes apf
WHERE apf.project_id = a.project_id 
AND apf.actividad_id = a.Id 
AND apf.semana = a.semanaActualizacion
```

### 2. Frontend: listadoActividades.view.php
**Ubicación:** `views/listado-actividades/listadoActividades.view.php`

**Cambios:**

#### 2.1 Agregar columna de acciones mejorada (línea ~771)
```javascript
{"defaultContent":"<button type='button' class='ver-proceso btn btn-outline-info btn-sm btn-action-gap' title='Ver proceso de generación' style='display:none'><i class='fa fa-info-circle fa-xs'></i></button><button type= 'button' class='editar btn btn-primary btn-sm btn-action-gap'  title='Editar'><i class='fa fa-edit fa-xs'></i></button><button type='button' class='eliminar btn btn-danger btn-sm btn-action-gap'  title='Eliminar'><i class='fa fa-trash-alt fa-xs'></i></button>"}
```

#### 2.2 Agregar columnDefs para mostrar/ocultar ícono según `auto_generado`
```javascript
{
    'targets': [0],
    'createdCell': function(td, cellData, rowData, row, col) {
        var $btnProceso = $(td).find('.ver-proceso');
        if (rowData.auto_generado == 1) {
            $btnProceso.show();
        } else {
            $btnProceso.hide();
        }
    }
}
```

#### 2.3 Agregar handler para el click en .ver-proceso
```javascript
var inicializarPopoverProceso = function(tbody, table) {
    $(tbody).off('click.verProceso', '.ver-proceso').on('click.verProceso', '.ver-proceso', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $btn = $(this);
        var $row = $btn.closest('tr');
        var data = table.row($row).data();
        
        if (!data || !data.fuentes_info) {
            return;
        }
        
        var fuentes = typeof data.fuentes_info === 'string' ? JSON.parse(data.fuentes_info) : data.fuentes_info;
        
        // Construir contenido del popover
        var html = '<div class="popover-proceso-familia">';
        html += '<div class="proceso-header"><strong>' + escaparHtml(data.actividad) + '</strong></div>';
        
        if (fuentes && fuentes.length > 0) {
            var fuente = fuentes[0];
            html += '<div class="proceso-seccion">';
            html += '<div class="proceso-label">Familia detectada:</div>';
            html += '<div class="proceso-valor">' + escaparHtml(fuente.familia || '-') + '</div>';
            html += '</div>';
            
            html += '<div class="proceso-seccion">';
            html += '<div class="proceso-label">Regla de matching:</div>';
            html += '<div class="proceso-valor">' + escaparHtml(fuente.regla || '-') + '</div>';
            html += '</div>';
            
            html += '<div class="proceso-seccion">';
            html += '<div class="proceso-label">Confianza:</div>';
            html += '<div class="proceso-valor"><span class="badge badge-' + (fuente.confianza >= 80 ? 'success' : (fuente.confianza >= 50 ? 'warning' : 'danger')) + '">' + (fuente.confianza || 0) + '%</span></div>';
            html += '</div>';
            
            html += '<div class="proceso-seccion">';
            html += '<div class="proceso-label">Contexto:</div>';
            html += '<div class="proceso-valor">' + escaparHtml(fuente.contexto || '-') + '</div>';
            html += '</div>';
            
            html += '<div class="proceso-seccion">';
            html += '<div class="proceso-label">Actividades del PG agrupadas (' + fuentes.length + '):</div>';
            html += '<ul class="proceso-lista">';
            for (var i = 0; i < fuentes.length; i++) {
                html += '<li>' + escaparHtml(fuentes[i].actividad || '-') + '</li>';
            }
            html += '</ul>';
            html += '</div>';
        }
        
        html += '</div>';
        
        // Mostrar popover
        $btn.popover({
            title: 'Proceso de Familia',
            content: html,
            html: true,
            trigger: 'click',
            placement: 'left',
            container: 'body',
            template: '<div class="popover popover-proceso" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
        }).popover('show');
        
        // Cerrar al hacer clic fuera
        $(document).one('click', function(e) {
            if (!$btn.is(e.target) && $btn.has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
                $btn.popover('hide');
            }
        });
    });
};
```

#### 2.4 Agregar CSS para el popover
```css
.popover-proceso {
    max-width: 400px;
}
.popover-proceso .proceso-header {
    padding-bottom: 8px;
    margin-bottom: 8px;
    border-bottom: 1px solid #dee2e6;
}
.popover-proceso .proceso-seccion {
    margin-bottom: 8px;
}
.popover-proceso .proceso-label {
    font-weight: 600;
    font-size: 12px;
    color: #6c757d;
    text-transform: uppercase;
    margin-bottom: 2px;
}
.popover-proceso .proceso-valor {
    font-size: 14px;
}
.popover-proceso .proceso-lista {
    margin: 0;
    padding-left: 16px;
    font-size: 12px;
    max-height: 150px;
    overflow-y: auto;
}
```

#### 2.5 Llamar a inicializarPopoverProceso en la función listar()
Después de la línea `obtener_data_editar("#dt_cliente tbody", table);` agregar:
```javascript
inicializarPopoverProceso("#dt_cliente tbody", table);
```

### 3. CSS: Agregar estilos al popover
**Opción A:** Agregar inline en la vista
**Opción B:** Agregar en un archivo CSS existente (recomendado: `public/css/styles.css` o similar)

## Orden de implementación

1. **Modificar ListadoActividadesApiController.php** - Agregar campos `auto_generado` y `fuentes_info` al query
2. **Modificar listadoActividades.view.php** - Agregar ícono, handler y estilos
3. **Probar** - Verificar que el ícono aparece solo en familias auto-generadas y que el popover muestra la información correcta

## Verificación

### Prueba manual
1. Ir a http://localhost:8081/listado-actividades
2. Verificar que las familias auto-generadas tienen el ícono de info-circle
3. Hacer clic en el ícono y verificar que se muestra el popover con:
   - Familia detectada
   - Regla de matching
   - Confianza (%)
   - Contexto
   - Lista de actividades del PG
4. Verificar que las familias creadas manualmente NO tienen el ícono

### Prueba de API
```bash
curl -X POST http://localhost:8081/api/listado-actividades/list -d "db=proyecto&semana=7" | jq '.data[0] | {auto_generado, fuentes_info}'
```

## Riesgos

1. **Performance:** El query con subqueries puede ser lento con muchos registros. Mitigar con índices en `actividad_programa_fuentes`.
2. **Popover positioning:** El popover puede salirse del viewport en filas cercanas a los bordes. Usar `placement: 'auto'` si es necesario.
3. **Datos nulos:** Manejar casos donde `fuentes_info` sea null o vacío.
