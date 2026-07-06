# E2E Test Suite — Last Planner AIA

Playwright end-to-end tests for `jbenite7/lastplanneraia-construccion`.

## Prerequisites

- Docker Compose running the app stack (`docker compose up -d --build db app adminer`)
- App reachable at `http://localhost:8081`
- Node.js 18+

## Setup

```bash
cd e2e
cp .env.e2e.example .env.e2e     # fille in local secrets
npm install
npm run install:browsers
```

## Run

| Command | What it does |
|---|---|
| `npm run test:smoke` | Login + 12 route render checks |
| `npm run test:deep` | LPS 2-week workflow + procurement flow |
| `npm test` | All tests |
| `npm run test:headed` | All tests with browser visible |
| `npm run test:debug` | Debug mode (step by step) |
| `npm run test:ui` | Playwright UI mode |
| `npm run report` | Open HTML report |

## Structure

```
e2e/
├── playwright.config.mjs
├── package.json
├── .env.e2e.example
├── README.md
├── support/
│   └── findings.mjs
└── tests/
    ├── setup/
    ├── smoke/
    │   └── routes.spec.mjs
    ├── permissions/
    └── workflows/
        ├── lps-two-weeks.spec.mjs
        └── procurement-flow.spec.mjs
```

## CI / Artifacts

- `test-results/report/` — HTML report
- `test-results/findings/` — per-test findings.md
- Screenshots, traces, and videos on failure

## Project Target

Tests run against **Da Porto** (Construcción) — `project_id=73`, `dbPrefix=da_porto`.