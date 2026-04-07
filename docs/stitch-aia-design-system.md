# AIA Last Planner Design System Brief

## Purpose

Create a new Stitch design project for the AIA Last Planner app.
The goal is a single, cohesive design system that can scale across the full product surface:

- 30 module families
- 4 variants per module
- Desktop linen
- Desktop dark
- Mobile linen
- Mobile dark

This brief is the source of truth for all generated screens.

## Brand Rules

Follow the AIA brand manual strictly.

- Visual language: Apple liquid glass like, premium, calm, layered, translucent, strong blur, fine borders, subtle shadows.
- Glassmorphism must be obvious on shells, cards, navs, drawers, modals, and panels.
- Do not use flat opaque containers.
- Dark mode must feel like dark glass, not black blocks.
- Primary family: Corporate Green `#1a3c2a`.
- Secondary/accent family: Construction Orange `#8b4011`.
- Supporting accent family: Architecture Blue `#1f4f82`.
- Projects Aqua stays only as a very restrained tertiary accent.
- Neutrals: Linen `#F4F1EA`, Alabaster `#FAFAFA`, warm grays.
- Use brand alert colors only for semantic states.
- Avoid loud gradients, pure black, pure white, and random rainbow palettes.
- Keep every screen dense but readable.

## Typography

- Headings and large metrics: Montserrat.
- Body, labels, forms, helper text, tables: Inter.
- Use no more than 2 font families.
- Prefer strong hierarchy over decorative typography.

## Design Principles

- Use one family of surfaces across the app.
- Dark mode should feel like liquid glass, not flat black.
- Linen mode should feel warm and premium, not sterile white.
- Desktop is for power users and dense planning data.
- Mobile is for quick review, edits, and simple actions.
- Keep accessibility and contrast correct.

## Core Design Tokens

### Colors

- Primary: `#1a3c2a`
- Secondary: `#8b4011`
- Accent blue: `#1f4f82`
- Accent aqua: `#00a499`
- Linen: `#F4F1EA`
- Alabaster: `#FAFAFA`
- Dark surface: `#1C1C1E`
- Dark card: `#2C2C2E`
- Text: warm charcoal and soft grays

### Shape and Depth

- Rounded corners everywhere.
- Prefer 12px to full-pill shapes.
- Use soft elevation only.
- Use glass blur on cards, nav, drawers, dialogs, and panels.

### Motion

- Motion must be subtle and fast.
- No distracting bounces.
- Keep transitions short and calm.

## Component Library

Design reusable components first.

- App shell
- Top navigation
- Side drawer
- Glass cards
- Buttons
- Inputs and selects
- Tabs
- Chips and badges
- Alerts and toasts
- Modals and drawers
- Data tables
- Spreadsheet-like grids
- Filters and legends
- Progress bars
- Empty states
- KPI cards

## Module Families

Build the same visual system across all module families.

### Priority First

1. Login
2. Projects
3. Programa General (PG)
4. Programacion Intermedia (PI)
5. Programacion Semanal (PS)

### Full App Coverage

Apply the same system to:

- PDC
- Contratos
- Listado de Actividades
- Indicadores
- Control de Cambios
- Profesionales
- Subcontratistas
- CNP
- CNC
- CIC
- Admin Login
- Admin Dashboard
- Admin Users
- Admin Projects
- Admin Password Reset screens

## Module Direction

### Login

- Minimal, elegant, high-trust.
- Corporate Green dominant, using the darker emerald tone.
- Centered glass card.
- Very clear hierarchy.

### Projects

- Card-based selector.
- Search and progress states.
- Corporate Green with restrained orange accents.

### PG / PI / PS

- Dense, spreadsheet-like, power-user focused.
- Construction Orange dominant, using the darker construction tone.
- Sticky toolbars, legends, filters, chips, and modal workflows.
- Mobile fallback should remain usable and intentional.

### PDC / Contratos / Actividades / Control de Cambios

- Table-first, operational, precise.
- Construction Orange dominant.
- Use semantic status colors sparingly.

### Indicators / Admin Dashboard

- Corporate control-room feel.
- KPI cards, report containers, tables, status summaries.

### People / Suppliers

- Editable tables plus mobile cards.
- Fast management, low friction.

### Admin Area

- Same brand family as the app.
- More operational and dense.
- Never visually disconnected from the main product.

## Responsive Variants

Create 4 variants for every module:

- Desktop Linen
- Desktop Dark
- Mobile Linen
- Mobile Dark

Rules:

- Desktop can be dense and information rich.
- Mobile should use stacked cards, simplified actions, and stronger hierarchy.
- Dark mode must preserve brand identity.
- Linen mode is the default warm light mode.

## State Rules

Use the brand alert palette for:

- Missing data
- Critical delay
- Delay
- Late completion
- On-time completion
- Active work
- Not started

States must inform behavior, not decorate it.

## Accessibility

- Maintain WCAG AA contrast.
- Make focus states obvious.
- Avoid color-only meaning.
- Keep tap targets usable on mobile.

## Out of Scope

- Do not design `registrate/` as a target screen.
- User creation lives in `admin/`.
- Do not create a generic SaaS look.

## Acceptance Criteria

- One coherent design family across the whole app.
- Priority modules established first.
- Same brand voice across light, dark, desktop, and mobile.
- The system feels like AIA, not a generic template.
- The result can scale to the full 30-module surface.
