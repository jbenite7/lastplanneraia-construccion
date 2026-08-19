#!/usr/bin/env python3
"""Clasifica cada !important por la FAMILIA del selector al que apunta.
Un !important contra CSS de vendor es defendible (el adaptador tiene que ganar).
Contra un selector propio del modulo es cascada rota adentro. Contarlos juntos
esconde justo la distincion que importa."""
import re, sys, json, glob, os

FAM = [
    # `#hot-container` / `#hotTable` / `.hot-` son el contenedor de la grilla: una regla
    # sobre `td` ahi dentro pinta una celda del vendor aunque el selector no lo nombre.
    ('handsontable', r'\.ht[A-Z_]|\.handsontable|\.wt[A-Z]|colHeader|rowHeader|\.htCore|\.ht_|#hot-container|#hotTable|\.hot-'),
    # `#dt_cliente` es el <table> que DataTables gobierna en Programacion Semanal.
    ('datatables',   r'dataTable|\bdt-|dataTables_|#dt_cliente'),
    ('select2',      r'select2'),
    ('tom-select',   r'\.ts-[a-z]'),
    ('sweetalert2',  r'swal2'),
    ('jquery-ui',    r'\.ui-[a-z]'),
    ('anychart',     r'anychart|\.acredits'),
    # `(?![-\w])` no es adorno: `\.btn\b` casa `.btn-filter-toggle` y `.btn-pdc-modern`,
    # que son clases PROPIAS del proyecto y no de Bootstrap. Lo destapo el muestreo manual
    # del 10% de la tanda 2: dos de veinte mal clasificados por ese solo motivo.
    ('bootstrap/adminlte', r'\.btn(?![-\w])|\.card(?![-\w])|\.form-control(?![-\w])|\.modal-(?:dialog|content|header|body|footer|backdrop)|\.nav-(?:link|item|tabs|pills)|\.navbar|main-sidebar|content-wrapper|\.custom-select|\.input-group|\.badge(?![-\w])|\.table(?![-\w])'),
    ('primitiva-aia', r'\.aia-'),
]

def quitar_comentarios(s):
    out, i = [], 0
    while i < len(s):
        if s[i] == '/' and i+1 < len(s) and s[i+1] == '*':
            fin = s.find('*/', i+2); hasta = len(s) if fin == -1 else fin+2
            out.append(''.join('\n' if c == '\n' else ' ' for c in s[i:hasta])); i = hasta
        else:
            out.append(s[i]); i += 1
    return ''.join(out)

res = {}
for ruta in sys.argv[1:]:
    src = quitar_comentarios(open(ruta, encoding='utf8').read()).split('\n')
    # Un selector puede ocupar varias lineas (:where(...) multilinea es lo normal aqui).
    # Acumular hasta el '{' y no quedarse con la ultima linea: si no, el selector de
    # `:where(\n .a,\n .b\n )` se lee como `)` y cae en la familia equivocada.
    sel, acc, cuenta, detalle = '', [], {}, []
    for n, l in enumerate(src, 1):
        t = l.strip()
        if t.startswith('@') or t in ('', '}'):
            if t.startswith('@') or t == '}':
                acc = []
        elif '{' in t:
            acc.append(t[:t.index('{')])
            sel = ' '.join(x.strip() for x in acc if x.strip())
            acc = []
        elif not t.endswith(';') and ':' not in t.split('/*')[0]:
            acc.append(t)
        if '!important' in l:
            fam = 'propio-del-modulo'
            for nombre, re_ in FAM:
                if re.search(re_, sel):
                    fam = nombre; break
            if not sel: fam = 'sin-selector-legible'
            cuenta[fam] = cuenta.get(fam, 0) + 1
            detalle.append({'linea': n, 'familia': fam, 'selector': sel[:120]})
    if detalle:
        res[ruta] = {'total': len(detalle), 'porFamilia': cuenta, 'detalle': detalle}
print(json.dumps(res))
