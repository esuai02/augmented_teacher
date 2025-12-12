# PRD: JSON-LD Viewer Optimization

## Introduction/Overview

Transform the existing ontology visualizer into a comprehensive JSON-LD viewing and analysis tool that maintains the powerful graph visualization while adding essential features for understanding and debugging ontology structures. The current tool only displays a graph view, making it difficult to quickly understand JSON-LD structure, see raw data, or work with @context definitions.

**Problem Statement**: Developers debugging ontology systems need to see both the semantic graph connections AND the raw JSON-LD source, but the current tool only offers graph visualization, forcing constant context switching between the visualizer and text editors.

**Goal**: Create a unified, developer-friendly JSON-LD viewer that provides multiple perspectives (graph, tree, raw JSON) on ontology data with validation, syntax highlighting, and quick navigation features.

## Goals

1. **Multi-View JSON-LD Analysis**: Provide graph, tree, and raw JSON views in a single interface
2. **Improved Structure Comprehension**: Add syntax highlighting, tree view, and @context tools for faster understanding
3. **Developer Productivity**: Reduce debugging time by 40% through integrated viewing and validation
4. **Professional Dashboard UI**: Create a polished, card-based interface with dark mode support
5. **Performance at Scale**: Handle medium-sized ontologies (100KB-1MB, ~50 rules) smoothly

## User Stories

### Primary User: Backend Developer (Ontology Debugging)
- As a developer debugging inference rules, I want to see the graph connections AND the raw JSON-LD simultaneously, so I can quickly correlate semantic relationships with actual data structures
- As a developer validating ontology structure, I want to expand/compact @context and validate JSON-LD, so I can ensure proper semantic definitions without external tools
- As a developer exploring an unfamiliar ontology, I want a tree view of the JSON structure, so I can quickly navigate to specific sections without parsing raw JSON
- As a developer working late hours, I want dark mode support, so I can reduce eye strain during extended debugging sessions
- As a developer revisiting recent work, I want to bookmark frequently accessed ontologies and view my history, so I can quickly return to previous analysis points

## Functional Requirements

### FR-1: View Management System
1.1. The system must provide a tabbed interface with three view modes: Graph View, Tree View, and Raw JSON View
1.2. Graph View must remain as the default view, maintaining all existing vis-network functionality
1.3. Raw JSON View must display syntax-highlighted JSON-LD with proper indentation
1.4. Tree View must show expandable/collapsible JSON structure with type indicators (@type, @id, @value)
1.5. The system must maintain view state when switching between tabs
1.6. The system must support mouseover tooltips in all views showing additional context

### FR-2: JSON-LD Processing Tools
2.1. The system must provide @context expand functionality using jsonld.expand()
2.2. The system must provide @context compact functionality using jsonld.compact()
2.3. The system must support flattened JSON-LD format using jsonld.flatten()
2.4. The system must validate JSON-LD structure and display errors with line numbers
2.5. Tool operations must complete within 2 seconds for files up to 1MB
2.6. The system must display processing results in a dedicated panel

### FR-3: Navigation & Search Features
3.1. The system must provide keyword search across all JSON-LD properties and values
3.2. Search results must highlight matches in all view modes
3.3. The system must support filtering by @type values
3.4. The system must provide a breadcrumb trail for Tree View navigation
3.5. The system must support bookmarking specific ontology files
3.6. The system must maintain a history of loaded ontologies (last 10 entries)

### FR-4: Dashboard UI Implementation
4.1. The system must use a card-based layout with distinct sections for input, tools, and views
4.2. Each functional area must be presented as a widget with clear visual hierarchy
4.3. The system must provide a dark mode toggle with persistent preference storage
4.4. The system must maintain the existing color coding for different node types
4.5. The system must be responsive and work on screens ≥1280px width
4.6. The system must use the existing CSS variable system for consistent theming

### FR-5: Ontology Information Display
5.1. The system must continue displaying ontology metadata (filename, rule count, emotion count, class count)
5.2. The system must add JSON-LD context information (@context keys count, namespaces)
5.3. The system must show file size and last loaded timestamp
5.4. The system must display validation status and any warnings

### FR-6: Performance Requirements
6.1. The system must load and display ontologies up to 1MB within 3 seconds
6.2. View switching must complete within 500ms
6.3. Search operations must complete within 1 second for files up to 1MB
6.4. The system must handle ontologies with up to 50 rules without performance degradation

### FR-7: Read-Only Mode
7.1. The system must NOT provide editing capabilities for JSON-LD content
7.2. All text areas and displays must be read-only or display-only
7.3. The system must allow copying content to clipboard
7.4. The system must display a clear indicator that the tool is in view-only mode

## Non-Goals (Out of Scope)

1. **Editing/Writing Features**: No JSON-LD editing, saving, or modification capabilities
2. **File Management**: No file upload, download, or server-side storage features
3. **Collaboration**: No sharing, commenting, or multi-user features
4. **Complex Validation**: No SHACL, OWL, or advanced semantic validation
5. **Version Control**: No diff viewing, versioning, or change tracking
6. **Mobile Support**: Not optimized for screens <1280px width
7. **Export Features**: No export to other formats (RDF/XML, Turtle, etc.)
8. **Authentication**: No user accounts or access control

## Design Considerations

### UI/UX Requirements
- **Layout**: Dashboard-style with card/widget-based sections
- **Color Scheme**: Maintain existing color palette with dark mode variant
- **Typography**: Use monospace fonts for code/JSON displays
- **Spacing**: Consistent padding/margins using existing CSS variables
- **Interactions**: Mouseover tooltips, smooth tab transitions, hover effects

### Component Hierarchy
```
├── Header Navigation (existing)
├── Ontology Loading Section (existing, enhanced)
├── Tabbed View Container (NEW)
│   ├── Graph View Tab (existing graph functionality)
│   ├── Tree View Tab (NEW - expandable JSON tree)
│   └── Raw JSON Tab (NEW - syntax highlighted JSON)
├── JSON-LD Tools Panel (NEW)
│   ├── @context Expand/Compact
│   ├── Flatten
│   └── Validate
├── Search & Filter Panel (NEW)
├── Bookmarks & History Sidebar (NEW)
└── Settings Panel (NEW - dark mode toggle)
```

### Visual Mockup References
- Graph View: Keep existing vis-network visualization
- Tree View: Similar to browser DevTools JSON inspector
- Raw JSON: Similar to VS Code JSON display with syntax highlighting
- Dashboard: Card-based layout similar to Grafana or modern admin dashboards

## Technical Considerations

### Technology Stack (Maintain Existing)
- **Visualization**: vis-network v9.1.9 (no changes)
- **JSON-LD Processing**: jsonld.js v8.3.3 (existing + new functions)
- **Syntax Highlighting**: Consider adding Prism.js or highlight.js (~30KB)
- **Frontend**: Vanilla JavaScript (no frameworks)
- **Styling**: CSS with CSS variables (existing pattern)

### Architecture Recommendations
1. **Modular JS Structure**: Separate modules for each view type
2. **State Management**: Simple state object for managing current view, bookmarks, history
3. **Event Bus**: Custom event system for view switching and data updates
4. **Local Storage**: For dark mode preference, bookmarks, and history

### Integration Requirements
- Must maintain compatibility with existing ontology file loading (`../examples/01_minimal_ontology.json`)
- Must preserve existing breadcrumb navigation to `inference_lab_v3.php`
- Must use existing CSS variable system for theming consistency

### Performance Optimization Strategies
- Lazy loading for Tree View (only expand visible nodes initially)
- Virtual scrolling for large JSON displays (if >1000 lines)
- Debounced search input (300ms delay)
- Cached @context expansion results

## Success Metrics

### Primary Success Metrics
1. **Debugging Time Reduction**: 40% faster time to locate and understand specific ontology rules (measured via user testing)
2. **Context Switching Reduction**: 80% reduction in switching between visualizer and text editor (user observation)
3. **Structure Comprehension**: Users can identify rule structure 3x faster using Tree View vs. raw JSON (A/B testing)

### Secondary Success Metrics
4. **User Satisfaction**: ≥85% positive feedback on dashboard UI and dark mode
5. **Performance**: All interactions complete within specified time limits (FR-6)
6. **Adoption**: Used as primary ontology debugging tool by development team within 2 weeks

### Quality Metrics
7. **Code Quality**: Maintain existing code structure and conventions
8. **Accessibility**: Keyboard navigation support for all primary features
9. **Browser Compatibility**: Works on Chrome, Firefox, Safari (latest versions)

## Open Questions

1. **Tree View Implementation**: Should we use a library (e.g., jstree) or build custom? (Trade-off: dependency vs. maintenance)
2. **Search Syntax**: Should we support regex or only plain text search? (Complexity vs. power user needs)
3. **Bookmark Storage**: LocalStorage limit is 5-10MB - is this sufficient for our use case?
4. **Syntax Highlighting Library**: Prism.js vs. highlight.js vs. custom implementation? (Size vs. features)
5. **View Synchronization**: Should selecting a node in Graph View highlight it in Tree/Raw views? (Complexity vs. UX benefit)
6. **Dark Mode Scope**: Should it apply to the entire page or just the tool sections? (Considering Moodle integration)

## Acceptance Criteria

The feature will be considered complete when:
1. ✅ All three view modes (Graph, Tree, Raw JSON) are functional and accessible via tabs
2. ✅ @context expand, compact, and flatten operations work correctly
3. ✅ JSON-LD validation displays accurate errors with line numbers
4. ✅ Search and filter functionality works across all views
5. ✅ Dark mode can be toggled and preference persists
6. ✅ Bookmarks and history are saved to localStorage
7. ✅ All FR-6 performance requirements are met
8. ✅ UI matches dashboard design specifications
9. ✅ No regression in existing graph visualization features
10. ✅ Read-only mode is enforced (no edit capabilities present)

---

**Document Version**: 1.0
**Created**: 2025-01-01
**Target Audience**: Junior Developer
**Estimated Effort**: Medium (2-3 weeks for junior developer)
