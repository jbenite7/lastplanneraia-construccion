# Vendor · DataTables

Mismo patrón que Handsontable y menor tamaño: **26 de los 48 selectores del vendor no los alcanza
ninguna hoja nuestra**.

## El censo

```
$ cat public/vendor/datatables/*.css | grep -oE "\.(dataTable[a-zA-Z_]*|dt-[a-zA-Z-]+|dataTables_[a-zA-Z]+|sorting[a-zA-Z_]*|paginate_[a-zA-Z]+)" | sort -u | wc -l
48
$ # los mismos, en adapters/datatables.css + vendor-datatables-legacy.css + programacion-semanal.css + styles.css + legacy-bridge.css
22
```

Cinco archivos de vendor: `jquery.dataTables.css`, `dataTables.bootstrap4.min.css`,
`buttons.bootstrap4.min.css`, `responsive.bootstrap4.min.css` y `dataTables.checkboxes.css`, 33 KB
en total.

| Familia | Sin alcanzar |
|---|---|
| **Alineación de celda y cabecera** (14) | `.dt-body-{center,justify,left,nowrap,right}`, `.dt-head-{center,justify,left,nowrap,right}`, `.dt-{center,justify,left,right}` |
| **Botones y colecciones** (4) | `.dt-button-background`, `.dt-button-collection`, `.dt-button-collection-title`, `.dt-button-info` |
| **Casillas** (4) | `.dt-checkboxes-cell`, `.dt-checkboxes-select`, `.dt-checkboxes-select-all`, `.dt-control` |
| **Ordenación deshabilitada** (4) | `.sorting_`, `.sorting_asc_disabled`, `.sorting_desc_disabled`, `.sorting_disabled` |
| **Estados de tabla** (4) | `.dataTables_processing`, `.dataTables_scrollFootInner`, `.dataTables_sizing`, `.dt-hasChild` |

→ `F0-210`

## Los dos que más pesan

- **`.dataTables_processing`** es el indicador de **cargando** de toda tabla con carga por servidor.
  Ningún token lo alcanza, y es uno de los escenarios que el recorrido de esta fase pide mirar
  explícitamente. → `F0-211`
- **Los cuatro `.sorting_*_disabled`** son el estado deshabilitado de la ordenación. `C-7` del
  registro del 3-ago ya anotó que **ninguna tabla alcanzable tiene ordenación activa hoy**, así que
  la regla está escrita y nadie la ha visto en acción. Ese hallazgo sigue exacto. → `F0-212`

## La paleta del vendor también es clara

```
  13 #111    7 #585858    7 #2b2b2b    6 #dcdcdc    6 #0c0c0c    5 #ddd    4 #d33333    4 #444
```

Mezcla grises casi negros (`#111`, `#0c0c0c`) con grises muy claros (`#dcdcdc`, `#ddd`) porque son
cinco hojas de tres orígenes distintos. → `F0-213`

## Dónde vive la deuda de DataTables en el código nuestro

**230 de los 1 520 `!important` del repositorio apuntan a selectores de DataTables**, y 159 de ellos
están en un solo archivo: `public/css/programacion-semanal.css`. El adaptador canónico
—`adapters/datatables.css`, 168 líneas— tiene **cero**.

Eso invierte la lectura habitual: la deuda de DataTables no está en su adaptador, está en el módulo
que lo esquiva. `vendor-datatables-legacy.css` (60 líneas, 7 `!important`) es el puente de
compatibilidad y también es pequeño. → `F0-214`

## Lo ya medido que cae aquí

**`F-6`** — en `/control-cambios`, DataTables pinta el `sEmptyTable` largo y debajo el
`dataTables_info` con `sInfoEmpty: "Sin solicitudes"`, que no añade nada. Es configuración de la
tabla, no CSS. → `F0-061`

## Lo que no se pudo medir aquí

`.dataTables_processing` solo aparece durante una carga; los `.sorting_*` solo con ordenación
activa, que hoy ninguna tabla alcanzable tiene. Ninguno de los dos es alcanzable por lectura
estática ni tiene escenario. → `bloqueadoPor: runtime-budgets-al-ci`
