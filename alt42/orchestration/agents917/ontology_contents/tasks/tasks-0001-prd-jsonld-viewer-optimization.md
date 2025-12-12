# Task List: JSON-LD Viewer Optimization

**Based on**: 0001-prd-jsonld-viewer-optimization.md

## Relevant Files

### Files to Modify
- `ontology_visualizer/ontology_visualizer.html` - Main HTML structure, add tabbed view container, tool panels, search UI, bookmarks sidebar
- `ontology_visualizer/ontology_app.js` - Add view management, tree view logic, JSON-LD tools, search/filter, bookmarks/history, state management
- `ontology_visualizer/style.css` - Add dashboard styles, dark mode variables, card layouts, tab styles, tree view styles

### Files to Create
- `ontology_visualizer/views/tree-view.js` - Tree view module with expandable JSON structure rendering
- `ontology_visualizer/views/raw-json-view.js` - Raw JSON view module with syntax highlighting
- `ontology_visualizer/tools/jsonld-processor.js` - JSON-LD processing tools (expand, compact, flatten, validate)
- `ontology_visualizer/utils/state-manager.js` - State management for views, bookmarks, history, preferences
- `ontology_visualizer/utils/search-engine.js` - Search and filter functionality across all views
- `ontology_visualizer/styles/dark-mode.css` - Dark mode theme variables and overrides
- `ontology_visualizer/styles/dashboard.css` - Dashboard card/widget styles

### Test Files
- `ontology_visualizer/tests/tree-view.test.js` - Unit tests for tree view functionality
- `ontology_visualizer/tests/jsonld-tools.test.js` - Unit tests for JSON-LD processing tools
- `ontology_visualizer/tests/search.test.js` - Unit tests for search engine
- `ontology_visualizer/tests/e2e/viewer-integration.test.js` - E2E tests using Playwright

### Notes
- Keep existing vis-network graph functionality intact (no regression)
- Use ES6 modules for new JavaScript files
- Maintain existing CSS variable system for theming consistency
- All new modules should be imported in ontology_app.js
- LocalStorage keys: `jsonld-viewer-preferences`, `jsonld-viewer-bookmarks`, `jsonld-viewer-history`

## Tasks

- [ ] 1.0 Dashboard UI Structure & Tabbed View System
  - [ ] 1.1 Update `ontology_visualizer.html` to add tabbed navigation container with three tabs: "Graph View", "Tree View", "Raw JSON View"
  - [ ] 1.2 Restructure existing graph container to be within the Graph View tab panel
  - [ ] 1.3 Add empty container divs for Tree View and Raw JSON View tab panels
  - [ ] 1.4 Create `ontology_visualizer/styles/dashboard.css` with card-based layout styles, widget containers, and visual hierarchy
  - [ ] 1.5 Update `ontology_visualizer/style.css` to add tab styles (active/inactive states, transitions, hover effects)
  - [ ] 1.6 Add tab switching logic to `ontology_visualizer/ontology_app.js` (show/hide panels, maintain state, smooth transitions)
  - [ ] 1.7 Implement view state management to preserve content when switching between tabs
  - [ ] 1.8 Add dashboard header section with ontology info cards (file name, rule count, emotion count, class count)

- [ ] 2.0 Tree View & Raw JSON View Implementation
  - [ ] 2.1 Create `ontology_visualizer/views/tree-view.js` module with ES6 export structure
  - [ ] 2.2 Implement tree node rendering function with expand/collapse icons (▶ ▼)
  - [ ] 2.3 Add type indicators for JSON-LD special properties (@type, @id, @value, @context)
  - [ ] 2.4 Implement recursive tree building for nested objects and arrays
  - [ ] 2.5 Add expand/collapse event handlers with animation transitions
  - [ ] 2.6 Create `ontology_visualizer/views/raw-json-view.js` module with ES6 export structure
  - [ ] 2.7 Implement syntax highlighting using Prism.js or highlight.js (~30KB, decision per PRD Open Questions)
  - [ ] 2.8 Add line numbers to raw JSON display
  - [ ] 2.9 Implement copy-to-clipboard functionality for raw JSON view
  - [ ] 2.10 Add CSS styles for tree view (indentation, icons, colors) to `style.css`
  - [ ] 2.11 Import and integrate tree-view.js and raw-json-view.js into `ontology_app.js`

- [ ] 3.0 JSON-LD Processing Tools Panel
  - [ ] 3.1 Create `ontology_visualizer/tools/jsonld-processor.js` module with ES6 export structure
  - [ ] 3.2 Implement `expandContext()` function using `jsonld.expand()` with error handling
  - [ ] 3.3 Implement `compactContext()` function using `jsonld.compact()` with error handling
  - [ ] 3.4 Implement `flattenJsonLd()` function using `jsonld.flatten()` with error handling
  - [ ] 3.5 Implement `validateJsonLd()` function with JSON-LD structure validation and error reporting with line numbers
  - [ ] 3.6 Add loading indicators for operations (target: <2 seconds for 1MB files per FR-2.5)
  - [ ] 3.7 Add tools panel UI section to `ontology_visualizer.html` with buttons for each operation
  - [ ] 3.8 Create results display panel for showing processed JSON-LD output
  - [ ] 3.9 Add event handlers in `ontology_app.js` to connect UI buttons to jsonld-processor functions
  - [ ] 3.10 Add CSS styles for tools panel (buttons, results display, loading states)

- [ ] 4.0 Search & Filter Functionality
  - [ ] 4.1 Create `ontology_visualizer/utils/search-engine.js` module with ES6 export structure
  - [ ] 4.2 Implement keyword search function that searches across all JSON-LD properties and values
  - [ ] 4.3 Add debounced search input handler (300ms delay per PRD technical considerations)
  - [ ] 4.4 Implement search result highlighting in Graph View (highlight matching nodes)
  - [ ] 4.5 Implement search result highlighting in Tree View (expand and highlight matching nodes)
  - [ ] 4.6 Implement search result highlighting in Raw JSON View (highlight matching text with background color)
  - [ ] 4.7 Add @type filter dropdown to search panel (populate dynamically from loaded ontology)
  - [ ] 4.8 Implement type filtering logic that filters nodes/entries by selected @type value
  - [ ] 4.9 Add breadcrumb trail component for Tree View navigation
  - [ ] 4.10 Add search panel UI to `ontology_visualizer.html` (search input, type filter dropdown, result count)
  - [ ] 4.11 Add CSS styles for search panel, highlights, and breadcrumbs
  - [ ] 4.12 Integrate search-engine.js into `ontology_app.js` and connect to all three views

- [ ] 5.0 Bookmarks, History, and Dark Mode Features
  - [ ] 5.1 Create `ontology_visualizer/utils/state-manager.js` module with ES6 export structure
  - [ ] 5.2 Implement `saveBookmark()` function to store ontology file references in localStorage (key: `jsonld-viewer-bookmarks`)
  - [ ] 5.3 Implement `loadBookmarks()` function to retrieve bookmarks from localStorage
  - [ ] 5.4 Implement `addToHistory()` function to maintain last 10 loaded ontologies (key: `jsonld-viewer-history`)
  - [ ] 5.5 Implement `loadHistory()` function to retrieve history from localStorage
  - [ ] 5.6 Implement `savePreferences()` function to store dark mode preference (key: `jsonld-viewer-preferences`)
  - [ ] 5.7 Implement `loadPreferences()` function to retrieve and apply saved preferences on page load
  - [ ] 5.8 Create `ontology_visualizer/styles/dark-mode.css` with dark theme CSS variables and color overrides
  - [ ] 5.9 Add dark mode toggle button to `ontology_visualizer.html` header
  - [ ] 5.10 Implement dark mode toggle logic that applies/removes dark mode class and saves preference
  - [ ] 5.11 Add bookmarks sidebar UI to `ontology_visualizer.html` with list of saved bookmarks
  - [ ] 5.12 Add history panel UI showing last 10 loaded ontologies with timestamps
  - [ ] 5.13 Add click handlers for bookmark and history items to reload selected ontology
  - [ ] 5.14 Add CSS styles for bookmarks sidebar, history panel, and dark mode toggle
  - [ ] 5.15 Integrate state-manager.js into `ontology_app.js` and initialize on page load

- [ ] 6.0 Integration, Testing, and Performance Optimization
  - [ ] 6.1 Update `ontology_visualizer/ontology_app.js` to import all new modules (tree-view, raw-json-view, jsonld-processor, state-manager, search-engine)
  - [ ] 6.2 Implement initialization sequence: load preferences → restore bookmarks/history → setup event listeners
  - [ ] 6.3 Add error boundary handling for all async operations with user-friendly error messages
  - [ ] 6.4 Implement lazy loading for Tree View (only render visible nodes initially, expand on demand)
  - [ ] 6.5 Add caching for @context expansion results to avoid redundant processing
  - [ ] 6.6 Create `ontology_visualizer/tests/tree-view.test.js` with unit tests for tree rendering, expand/collapse, type indicators
  - [ ] 6.7 Create `ontology_visualizer/tests/jsonld-tools.test.js` with unit tests for expand, compact, flatten, validate functions
  - [ ] 6.8 Create `ontology_visualizer/tests/search.test.js` with unit tests for keyword search, type filtering, highlighting
  - [ ] 6.9 Create `ontology_visualizer/tests/e2e/viewer-integration.test.js` using Playwright for E2E testing of tab switching, tool operations, search
  - [ ] 6.10 Verify performance targets: <3s load for 1MB files, <500ms view switching, <1s search operations (FR-6)
  - [ ] 6.11 Run regression tests to ensure existing graph visualization features work correctly
  - [ ] 6.12 Validate read-only mode is enforced (all displays are read-only, copy buttons work)
  - [ ] 6.13 Test keyboard navigation support for all primary features (accessibility requirement)
  - [ ] 6.14 Test on Chrome, Firefox, Safari latest versions (browser compatibility requirement)
  - [ ] 6.15 Create final validation checklist matching PRD Acceptance Criteria (10 items)

---

**Status**: All sub-tasks generated. Ready for implementation by junior developer.

**Implementation Notes**:
- Follow existing code patterns in `ontology_app.js` for consistency
- Use existing CSS variable system from `style.css` for theming
- Maintain breadcrumb navigation to `inference_lab_v3.php`
- Test each major task completion before moving to next task
- Refer to PRD for detailed functional requirements and success metrics
