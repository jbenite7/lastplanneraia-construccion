#!/usr/bin/env python3
"""
Diagnóstico de Cobertura — Biblioteca Maestra PDC v1.0
Mide qué % de actividades del Programa General matchean contra las familias de la biblioteca.
Agrupa actividades sin match por tema para guiar la expansión de text_patterns.

Uso: python3 database/seeds/diagnostic_cobertura.py [--v10 path/to/v1.0.json]
"""

import json, re, os, sys
from collections import Counter, defaultdict

# Paths
REPO_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
V10_PATH = os.path.join(REPO_DIR, 'database', 'seeds', 'biblioteca_maestra_pdc_source_of_truth_v1_0.json')
GOLDEN_PATH = os.path.join(REPO_DIR, 'Downloads' if os.path.exists(os.path.join(REPO_DIR, 'Downloads')) else '', 'golden_dataset_pg.json')
if not os.path.exists(GOLDEN_PATH):
    GOLDEN_PATH = '/Users/juanfelipebenitezramos/Downloads/golden_dataset_pg.json'

# Allow override
for i, arg in enumerate(sys.argv):
    if arg == '--v10' and i + 1 < len(sys.argv):
        V10_PATH = sys.argv[i + 1]

def load_json(path):
    if not os.path.exists(path):
        print(f"ERROR: {path} not found")
        sys.exit(1)
    with open(path, 'r', encoding='utf-8') as f:
        return json.load(f)

def normalize_text(text):
    """Normalize text for matching: upper, remove accents, strip HTML remnants."""
    if not text:
        return ''
    text = text.upper()
    text = re.sub(r'<[^>]+>', ' ', text)
    text = re.sub(r'&[A-Za-z]+;', ' ', text)
    text = re.sub(r'[Á]', 'A', text)
    text = re.sub(r'[É]', 'E', text)
    text = re.sub(r'[Í]', 'I', text)
    text = re.sub(r'[Ó]', 'O', text)
    text = re.sub(r'[Ú]', 'U', text)
    text = re.sub(r'[Ñ]', 'N', text)
    text = re.sub(r'\s+', ' ', text).strip()
    return text

def match_activity(text, chapter, families):
    """Try to match an activity against all families. Returns (matched, family_code)."""
    combined = normalize_text(text + ' | ' + (chapter or ''))
    
    for fam in families:
        patterns = fam.get('text_patterns', '')
        if not patterns:
            continue
        for pat in patterns.split('|'):
            pat = pat.strip()
            if not pat:
                continue
            # Use regex OR simple substring match
            if pat in combined or re.search(re.escape(pat), combined, re.IGNORECASE):
                return True, fam['family_code']
    return False, None

def extract_theme(actividad, chapter):
    """Extract the main theme keyword from an activity."""
    text = normalize_text(actividad).split('|')[0].strip()
    # Remove chapter tags
    text = re.sub(r'\[CAPITULO[^\]]*\]', '', text).strip()
    words = text.split()
    
    # Skip common stop words
    stop_words = {'PARA', 'CON', 'POR', 'LAS', 'LOS', 'QUE', 'INCLUYE', 'CADA', 'TODO', 'DEBE', 'PUEDE', 'DEL', 'E', 'Y', 'EN', 'DE', 'LA', 'EL', 'SU', 'AL', 'SE', 'NO', 'MAS', 'UN', 'UNA', 'A', 'CAPITULO', 'ENTRE', 'HASTA', 'DESDE', 'DURANTE'}
    
    if words:
        # Try first noun-like word (length >= 4, not stop word)
        for w in words:
            clean = re.sub(r'[^A-Z]', '', w)
            if len(clean) >= 4 and clean not in stop_words:
                return clean
        # Fallback: first word
        return words[0] if words else 'OTRO'
    return 'OTRO'

def run_diagnostic(v10_path=V10_PATH, golden_path=GOLDEN_PATH, filter_projects=None):
    """Run the full coverage diagnostic."""
    v10 = load_json(v10_path)
    golden = load_json(golden_path)
    
    families = v10['families']
    activities = golden.get('activities', [])
    
    if filter_projects:
        activities = [a for a in activities if a.get('project_id') in filter_projects]
    
    matched = []
    unmatched = []
    family_hits = Counter()
    family_code_map = {}
    for fam in families:
        family_code_map[fam['family_code']] = fam
    
    for act in activities:
        text = act.get('actividad', '')
        chapter = act.get('chapter', '')
        is_match, fam_code = match_activity(text, chapter, families)
        act['matched_family'] = fam_code
        
        if is_match:
            matched.append(act)
            family_hits[fam_code] += 1
        else:
            unmatched.append(act)
    
    total = len(activities)
    matched_count = len(matched)
    coverage_pct = round(matched_count / total * 100, 1) if total > 0 else 0
    
    # Group unmatched by theme
    theme_groups = defaultdict(list)
    for act in unmatched:
        theme = extract_theme(act.get('actividad', ''), act.get('chapter', ''))
        theme_groups[theme].append(act)
    
    # For each theme group, find which families it COULD belong to
    theme_analysis = []
    for theme, acts in sorted(theme_groups.items(), key=lambda x: -len(x[1])):
        # Get sample texts
        sample_texts = [a.get('actividad', '')[:80] for a in acts[:5]]
        sample_chapters = list(set(a.get('chapter', '') for a in acts if a.get('chapter')))
        
        # Try to find existing family that could match
        suggested_families = []
        for fam in families:
            name = normalize_text(fam['canonical_name'])
            cat = normalize_text(fam.get('category', ''))
            # Check if theme relates to family name or category
            if theme in name or theme in cat:
                suggested_families.append(fam['family_code'])
        
        theme_analysis.append({
            'theme': theme,
            'count': len(acts),
            'sample_texts': sample_texts[:3],
            'sample_chapters': sample_chapters[:3],
            'suggested_families': suggested_families[:3],
            'project_ids': list(set(a.get('project_id', 0) for a in acts))
        })
    
    # By project
    project_stats = {}
    for pid in sorted(set(a.get('project_id', 0) for a in activities)):
        p_acts = [a for a in activities if a.get('project_id') == pid]
        p_total = len(p_acts)
        p_matched = sum(1 for a in p_acts if a.get('matched_family'))
        p_cov = round(p_matched / p_total * 100, 1) if p_total > 0 else 0
        project_stats[pid] = {
            'total': p_total,
            'matched': p_matched,
            'coverage_pct': p_cov,
            'families_detected': len(set(a['matched_family'] for a in p_acts if a.get('matched_family')))
        }
    
    # Top matched families
    top_families = family_hits.most_common(30)
    
    return {
        'total_activities': total,
        'matched_activities': matched_count,
        'unmatched_activities': len(unmatched),
        'coverage_pct': coverage_pct,
        'unique_families_detected': len(family_hits),
        'total_families_in_catalog': len(families),
        'top_families': [{'code': f[0], 'name': family_code_map.get(f[0], {}).get('canonical_name', ''), 'hits': f[1]} for f in top_families],
        'project_stats': project_stats,
        'unmatched_by_theme': theme_analysis[:50],
        'families_without_hits': [f['family_code'] for f in families if f['family_code'] not in family_hits]
    }

def print_report(result):
    """Print a human-readable report."""
    print(f"{'='*60}")
    print(f"DIAGNÓSTICO DE COBERTURA — Biblioteca Maestra PDC v1.0")
    print(f"{'='*60}")
    print(f"\n📊 COBERTURA GLOBAL")
    print(f"  Actividades: {result['total_activities']}")
    print(f"  Matcheadas:  {result['matched_activities']} ({result['coverage_pct']}%)")
    print(f"  Sin match:   {result['unmatched_activities']} ({round(100 - result['coverage_pct'], 1)}%)")
    print(f"  Familias detectadas: {result['unique_families_detected']} de {result['total_families_in_catalog']}")
    
    print(f"\n📊 POR PROYECTO")
    for pid, stats in sorted(result['project_stats'].items()):
        name = ""
        if pid == 27: name = "JMC"
        elif pid in (72, 73, 74): name = "Da Porto"
        families_str = f" ({stats['families_detected']} familias)"
        print(f"  Project {pid} {name}: {stats['coverage_pct']}% ({stats['matched']}/{stats['total']}){families_str}")
    
    print(f"\n📊 TOP FAMILIAS MÁS DETECTADAS")
    for f in result['top_families'][:15]:
        print(f"  {f['code']}: {f['hits']} hits — {f['name']}")
    
    print(f"\n📊 ACTIVIDADES SIN MATCH POR TEMA")
    for t in result['unmatched_by_theme'][:30]:
        suggestion = f" → sugerencia: {', '.join(t['suggested_families'])}" if t['suggested_families'] else ""
        print(f"  {t['theme']}: {t['count']} veces{suggestion}")
        for s in t['sample_texts']:
            print(f"    ej: \"{s[:70]}\"")
    
    print(f"\n📊 FAMILIAS SIN NINGÚN HIT ({len(result['families_without_hits'])} de {result['total_families_in_catalog']})")
    for fc in result['families_without_hits'][:15]:
        print(f"  {fc}")
    if len(result['families_without_hits']) > 15:
        print(f"  ... y {len(result['families_without_hits']) - 15} más")
    
    print(f"\n{'='*60}")
    return result

if __name__ == '__main__':
    import sys
    filter_pids = None
    if '--project' in sys.argv:
        idx = sys.argv.index('--project')
        filter_pids = [int(sys.argv[idx + 1])]
    
    result = run_diagnostic(filter_projects=filter_pids)
    print_report(result)
    
    # Save output
    output_path = os.path.join(os.path.dirname(V10_PATH), 'diagnostico_cobertura.json')
    with open(output_path, 'w', encoding='utf-8') as f:
        json.dump(result, f, ensure_ascii=False, indent=2)
    print(f"\nReporte guardado en: {output_path}")