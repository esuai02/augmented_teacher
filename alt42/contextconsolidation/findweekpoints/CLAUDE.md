# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the WrongAnswerMap project, a comprehensive educational analytics system for analyzing student wrong answers and providing personalized learning recommendations. The system consists of:

- **Backend**: Python 3.12 + FastAPI 0.111 for REST/GraphQL APIs
- **Frontend**: Next.js 15 / React 19 (TypeScript) with Reagraph & D3 for graph visualization
- **Databases**: PostgreSQL 16 (relational) + Neo4j 5 (graph database)
- **Infrastructure**: Docker 24 + Docker Compose, GitHub Actions CI/CD

## Key Directory Structure

```
/app                    → FastAPI backend
/app/api               → REST & GraphQL endpoints
/app/services          → Business logic (analysis, tagging, recommendations)
/app/db                → ORM and Neo4j drivers
/frontend              → Next.js frontend
/frontend/components   → Common components
/frontend/features/map → Graph map UI
/scripts               → ETL & batch scripts
/tests                 → pytest / Playwright tests
/docs                  → Technical documentation
/infra                 → Infrastructure as Code (Docker, terraform)
```

## Development Commands

### Build and Run
```bash
make build    # docker compose build --parallel
make test     # pytest && pnpm test
make lint     # ruff + mypy + eslint + prettier
make deploy   # gh workflow dispatch deploy
```

### Python Environment Setup
```bash
# Create and activate virtual environment
python -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate

# Install dependencies
pip install -r requirements.txt
```

### Running the Main Application
```bash
# Run Flask app (main augmented_teacher system)
python app.py

# The app will be available at http://127.0.0.1:5000
```

## Code Conventions

- **Python**: PEP 8 compliant, use ruff + black formatter
- **JavaScript/TypeScript**: ESLint + Prettier
- **Commits**: Use commitizen semantic commit format
- **Branching**: Trunk-based development (main + short-lived feature branches)

## Key Technical Context

### Graph Database Integration
The system uses Neo4j for storing concept nodes and relationships between educational topics. Key entities:
- **Concept Node**: 개념(교과 소주제) 정점
- **Wrong-Answer Note**: 학생의 오답 기록
- **Topic Tag**: 문항에 부여된 소주제 라벨

### Performance Considerations
- Graph node queries > 500 require dynamic clustering and pagination
- Mobile visualization targets 60 FPS with canvas-based rendering fallback
- Wrong answer analysis pipeline runs weekly batch (Friday 02:00 KST) with 30min SLA

### Security and Privacy
- Sensitive data (PII) must be SHA-256 hashed or have attributes removed before storage
- Never expose or log secrets and API keys
- All database queries should use prepared statements

## Important Scripts

### Allowed Scripts
- `scripts/analyze_wrong_answers.py` - Wrong answer analysis ETL
- `scripts/import_curriculum.py` - Curriculum structure/content link loading

### Forbidden Scripts (DO NOT RUN)
- `scripts/drop_production_db.py` - Production DB deletion
- Any scripts that export raw PII data

## Areas Not to Modify
- `/infra/prod` - Production infrastructure configuration
- `/migrations/history` - Migration history files

## Testing Approach

The project uses:
- **pytest** for Python unit/integration tests
- **Playwright** for E2E testing
- **pnpm test** for JavaScript/TypeScript tests

Always verify tests pass before committing changes.

## Environment Configuration

- Environment variables are managed via `.env` and `.env.example`
- Docker Compose provides single-command local stack setup
- GitHub Actions automate testing and deployment

## API Authentication

The system integrates with Moodle LMS for authentication. Key points:
- User sessions managed via Moodle integration
- Role-based access control using `mdl_user_info_data`
- Custom tables for persona modes and message transformations

## Moodle Server Context Considerations

- Current project is located within a Moodle server's internal folder
- Developing a service that connects and retrieves information from Moodle's MySQL database
- Ensure all database interactions and service implementations consider Moodle's specific database schema and requirements