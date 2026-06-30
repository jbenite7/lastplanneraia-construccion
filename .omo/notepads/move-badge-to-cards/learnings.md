# Learnings: Move Pre-Construcción Badge from Navbar to Cards

## Task: Remove "Pre-Construcción" badge from navbar header

### What was done
- Removed lines 43-47 from `src/View/Components/NavbarComponent.php` — the `<?php if ($isPreConstruccion): ?> ... <?php endif; ?>` block containing the `<span class="badge badge-warning">Pre-Construcción</span>` element inside the `<a class="navbar-brand">` tag.

### What was preserved
- The `$isPreConstruccion` variable declaration (line 10): `$isPreConstruccion = ($_SESSION['area'] ?? 'Construccion') === 'Pre-Construccion';`
- The conditional nav-item hiding block (line 104): `<?php if (!$isPreConstruccion): ?>` which hides Listado de Actividades, PDC, Contratos, CIC for Pre-Construcción projects.

### Verification
- `grep -n "Pre-Construcción" NavbarComponent.php` → returns only the comment on line 9 (the detection logic)
- `grep -n "isPreConstruccion" NavbarComponent.php` → lines 10 and 104
- `php -l` → No syntax errors detected

### Notes
- The comment on line 9 (`// Detectar si el proyecto es Pre-Construcción`) was intentionally preserved since it documents the variable below. If a pure-zero-result for "Pre-Construcción" is required, that comment can be removed separately.
