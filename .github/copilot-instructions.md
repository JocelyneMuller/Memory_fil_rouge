# Instructions Copilot - Memory Project

## Architecture Overview
This is a full-stack web application with a PHP backend API and Vue.js frontend, designed to manage project portfolios.

### Structure
- **Backend**: PHP REST API with MVC pattern (`backend/`)
- **Frontend**: Vue 3 + Vite SPA (`frontend/`)
- **Database**: MySQL with schema defined in `conception/BDD/Script_SQL.sql`
- **Development**: MAMP local server environment

## Key Patterns & Conventions

### Backend API Structure
- **Entry point**: `backend/index.php` - Simple router using `?loc=` parameter
- **Controllers**: Located in `controllers/` - Use dependency injection pattern with PDO
- **Models**: Located in `models/` - Direct PDO database interaction, no ORM
- **Routing pattern**: `?loc=projects&action=list&category=web` (GET parameters only)

### Database Conventions
- Table names: PascalCase (`Project`, `Category`, `Role`)
- Primary keys: `id_TableName` format (e.g., `id_Project`)
- Foreign keys: `TableName_id_TableName` format
- Unique fields: suffix with `_Unique` (e.g., `Name_Unique`)

### Frontend Architecture
- **Entry component**: `src/App.vue` imports main components
- **API calls**: Direct fetch() to `http://localhost:8888/PFR/Memory/backend/`
- **Component structure**: Organized by feature in `components/` subdirectories
- **No router**: Single page application without Vue Router

## Development Workflow

### Local Development Setup
1. **MAMP**: Backend runs on `localhost:8888/PFR/Memory/backend/`
2. **Vite dev server**: Frontend runs on `localhost:5173`
3. **Database**: MySQL accessible via MAMP with credentials `root:root`

### Key Commands
```bash
# Frontend development
cd frontend
npm install
npm run dev        # Starts Vite dev server
npm run build      # Production build

# Database setup
# Import conception/BDD/Script_SQL.sql into MAMP MySQL
```

### CORS Configuration
Backend sets specific CORS headers for `localhost:5173` in `index.php`. Update when deploying or changing dev server ports.

## Code Examples

### Adding New API Endpoint
1. Add case in `backend/controllers/projects.php` `run()` method
2. Implement method following existing pattern with model injection
3. Update model in `models/Projects_model.php` with PDO prepared statements

### Adding New Vue Component
- Place in appropriate `components/` subdirectory
- Import and register in parent component
- Use composition API or options API consistently with existing code

## Recommended Patterns & Best Practices

### Error Handling & Validation
- **Backend**: Use `Validator` utility class for input sanitization
- **API Responses**: Standardize with `ApiResponse::success()` and `ApiResponse::error()`
- **Frontend**: Implement `useApi()` composable for centralized error handling
- **Security**: Always use prepared statements (already implemented in models)

### Component Organization Strategy
```
components/
├── layout/           # Header, Footer, Navigation
├── ui/              # Reusable components (Button, Modal, Card)
├── projects/        # Project-specific components
├── competences/     # Competence management
└── notes/           # Note-taking features
```

### Authentication Architecture (Planned)
- **JWT + Sessions**: Server-side session storage with JWT tokens
- **Security**: Implement rate limiting and secure cookie configuration
- **Authorization**: Role-based access control using existing `Role` table

### Development & Deployment Tools
- **Build**: Vite (frontend) + Composer (backend dependencies)
- **CI/CD**: GitHub Actions for automated testing and deployment
- **Testing**: PHPUnit (backend), Vitest (frontend), Playwright (E2E)
- **Hosting**: Traditional PHP hosting (OVH/Ionos) for production

## Important Notes
- Database connection uses hardcoded credentials in `config/database.php` - move to environment variables
- CORS configured for development - update for production domains
- Error handling currently minimal - implement centralized error management
- No authentication system yet - JWT + sessions recommended for this architecture