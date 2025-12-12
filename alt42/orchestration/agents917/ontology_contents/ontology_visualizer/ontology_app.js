import jsonld from 'https://cdn.jsdelivr.net/npm/jsonld@8.3.3/+esm';
import {
    initTreeView,
    expandToDepth,
    collapseAll,
    searchTreeView,
    highlightTreeMatches,
    clearTreeHighlights,
    focusTreeResult,
    getTreeMatchCount
} from './views/tree-view.js';
import { initRawJsonView, updateJsonDisplay } from './views/raw-json-view.js';
import { expandContext, compactContext, flattenJsonLd, validateJsonLd, getDocumentStats } from './tools/jsonld-processor.js';
import { searchJsonLd, filterByType, filterByProperty, getAllTypes, getAllProperties, highlightSearchTerm, getSearchStats } from './utils/search.js';

// DOM elements
const jsonldInput = document.getElementById('jsonld-input');
const visualizeBtn = document.getElementById('visualize-btn');
const exampleBtn = document.getElementById('example-btn');
const clearBtn = document.getElementById('clear-btn');
const recenterBtn = document.getElementById('recenter-btn');
const expandLevelBtn = document.getElementById('expand-level-btn');
const collapseLevelBtn = document.getElementById('collapse-level-btn');
const messageBox = document.getElementById('message-box');
const graphContainer = document.getElementById('graph-container');

// Ontology loading buttons
const loadOntologyBtn = document.getElementById('load-ontology-btn');
const loadBackupBtn = document.getElementById('load-backup-btn');
const ontologyInfo = document.getElementById('ontology-info');
const infoFilename = document.getElementById('info-filename');
const infoRules = document.getElementById('info-rules');
const infoEmotions = document.getElementById('info-emotions');
const infoClasses = document.getElementById('info-classes');

// View tabs and panels
const viewTabs = document.querySelectorAll('.view-tab');
const viewPanels = {
    graph: document.getElementById('graph-panel'),
    tree: document.getElementById('tree-panel'),
    raw: document.getElementById('raw-panel'),
    tools: document.getElementById('tools-panel')
};

// Raw JSON view elements
const copyJsonBtn = document.getElementById('copy-json-btn');

// Tools panel elements
const expandBtn = document.getElementById('expand-btn');
const compactBtn = document.getElementById('compact-btn');
const flattenBtn = document.getElementById('flatten-btn');
const validateBtn = document.getElementById('validate-btn');
const toolsLoading = document.getElementById('tools-loading');
const toolsResults = document.getElementById('tools-results');
const resultsTitle = document.getElementById('results-title');
const resultsStats = document.getElementById('results-stats');
const resultsContent = document.getElementById('results-content');
const resultsCode = document.getElementById('results-code');
const clearResultsBtn = document.getElementById('clear-results-btn');

// Search and filter elements
const searchInput = document.getElementById('search-input');
const searchBtn = document.getElementById('search-btn');
const clearSearchBtn = document.getElementById('clear-search-btn');
const caseSensitiveCheck = document.getElementById('case-sensitive-check');
const exactMatchCheck = document.getElementById('exact-match-check');
const searchKeysCheck = document.getElementById('search-keys');
const searchValuesCheck = document.getElementById('search-values');
const searchTypesCheck = document.getElementById('search-types');
const typeFilter = document.getElementById('type-filter');
const propertyFilter = document.getElementById('property-filter');
const applyFilterBtn = document.getElementById('apply-filter-btn');
const clearFilterBtn = document.getElementById('clear-filter-btn');
const searchResultsPanel = document.getElementById('search-results-panel');
const searchResultsTitle = document.getElementById('search-results-title');
const prevResultBtn = document.getElementById('prev-result-btn');
const nextResultBtn = document.getElementById('next-result-btn');
const resultCounter = document.getElementById('result-counter');
const closeResultsBtn = document.getElementById('close-results-btn');
const searchResultsStats = document.getElementById('search-results-stats');
const searchResultsList = document.getElementById('search-results-list');

// Network and data objects
let network = null;
let nodesDataset = new vis.DataSet();
let edgesDataset = new vis.DataSet();
let jsonldData = null;
let expandedNodes = new Set();
let expandedMapping = {};

// Search and filter state
let searchResults = [];
let currentSearchIndex = -1;
let highlightedNodes = new Set();

// Network layout options
const options = {
    layout: {
        hierarchical: {
            enabled: true,
            direction: 'LR',
            sortMethod: 'directed',
            nodeSpacing: 180,
            levelSeparation: 250
        }
    },
    physics: {
        enabled: false
    },
    interaction: {
        dragNodes: true,
        dragView: true,
        zoomView: true,
        hover: true
    },
    nodes: {
        shape: 'box',
        margin: 10,
        font: {
            size: 14,
            multi: false
        },
        borderWidth: 1,
        shadow: true,
        widthConstraint: {
            maximum: 200
        },
        heightConstraint: {
            minimum: 30
        }
    },
    edges: {
        arrows: {
            to: {enabled: true, scaleFactor: 1}
        },
        color: {
            color: '#2c3e50',
            highlight: '#34495e'
        },
        smooth: {
            type: 'cubicBezier',
            forceDirection: 'horizontal'
        },
        font: {
            align: 'middle',
            size: 12
        }
    }
};


function initApp() {
    initNetwork();

    recenterBtn.addEventListener('click', recenterGraph);
    visualizeBtn.addEventListener('click', handleVisualize);
    exampleBtn.addEventListener('click', loadExample);
    clearBtn.addEventListener('click', clearAll);
    expandLevelBtn.addEventListener('click', expandLevel);
    collapseLevelBtn.addEventListener('click', collapseLevel);

    // Ontology loading
    loadOntologyBtn.addEventListener('click', () => loadOntologyFile('../ontology/ontology.jsonld'));
    loadBackupBtn.addEventListener('click', () => loadOntologyFile('../ontology/ontology.backup.jsonld'));

    // Tools panel operations
    if (expandBtn) expandBtn.addEventListener('click', handleExpandContext);
    if (compactBtn) compactBtn.addEventListener('click', handleCompactContext);
    if (flattenBtn) flattenBtn.addEventListener('click', handleFlattenJsonLd);
    if (validateBtn) validateBtn.addEventListener('click', handleValidateJsonLd);
    if (clearResultsBtn) clearResultsBtn.addEventListener('click', clearToolsResults);

    // Search and filter operations
    if (searchBtn) searchBtn.addEventListener('click', executeSearch);
    if (clearSearchBtn) clearSearchBtn.addEventListener('click', clearSearch);
    if (prevResultBtn) prevResultBtn.addEventListener('click', navigateToPrevResult);
    if (nextResultBtn) nextResultBtn.addEventListener('click', navigateToNextResult);
    if (closeResultsBtn) closeResultsBtn.addEventListener('click', closeSearchResults);
    if (applyFilterBtn) applyFilterBtn.addEventListener('click', applyFilters);
    if (clearFilterBtn) clearFilterBtn.addEventListener('click', clearFilters);

    // Search on Enter key
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                executeSearch();
            }
        });
    }

    // View tab switching
    viewTabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            const viewName = tab.getAttribute('data-view');
            if (viewName) {
                switchView(viewName);
            }
        });
    });
}


// Load ontology file from server
async function loadOntologyFile(filePath) {
    try {
        showMessage('온톨로지 파일을 로드하는 중...');

        const response = await fetch(filePath);
        if (!response.ok) {
            throw new Error(`파일을 찾을 수 없습니다: ${filePath}`);
        }

        const ontology = await response.json();

        // Display ontology information
        displayOntologyInfo(ontology, filePath);

        // Load into textarea
        jsonldInput.value = JSON.stringify(ontology, null, 2);

        // Auto-visualize
        await handleVisualize();

        showMessage(`✅ 온톨로지 로드 완료: ${filePath.split('/').pop()}`);

    } catch (error) {
        console.error('Error loading ontology:', error);
        showMessage(`❌ 온톨로지 로드 실패: ${error.message}`, true);
    }
}


// Display ontology information
function displayOntologyInfo(ontology, filePath) {
    const graph = ontology['@graph'] || [];

    // Count rules, emotions, and classes
    let ruleCount = 0;
    let emotionCount = 0;
    let classCount = 0;

    for (const item of graph) {
        const type = item['@type'];
        if (type === 'InferenceRule') {
            ruleCount++;
        } else if (type === 'Emotion') {
            emotionCount++;
        } else if (type === 'rdfs:Class' || type === 'Class') {
            classCount++;
        }
    }

    // Update info display
    infoFilename.textContent = filePath.split('/').pop();
    infoRules.textContent = `${ruleCount}개`;
    infoEmotions.textContent = `${emotionCount}개`;
    infoClasses.textContent = `${classCount}개`;

    // Show info section
    ontologyInfo.classList.add('show');
}


// Switch between view tabs
function switchView(viewName) {
    // Remove active class from all tabs
    viewTabs.forEach(tab => {
        tab.classList.remove('active');
    });

    // Remove active class from all panels
    Object.values(viewPanels).forEach(panel => {
        if (panel) {
            panel.classList.remove('active');
        }
    });

    // Add active class to selected tab
    const selectedTab = document.querySelector(`.view-tab[data-view="${viewName}"]`);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }

    // Add active class to selected panel
    const selectedPanel = viewPanels[viewName];
    if (selectedPanel) {
        selectedPanel.classList.add('active');
    }

    // Update view content based on current data
    if (viewName === 'tree' && jsonldData) {
        updateTreeView(jsonldData);
    } else if (viewName === 'raw' && jsonldData) {
        updateRawJsonView(jsonldData);
    }
}

/**
 * Get currently active view name
 * @returns {string} Active view name ('graph', 'tree', 'raw', or 'tools')
 */
function getActiveView() {
    if (viewPanels.graph?.classList.contains('active')) return 'graph';
    if (viewPanels.tree?.classList.contains('active')) return 'tree';
    if (viewPanels.raw?.classList.contains('active')) return 'raw';
    if (viewPanels.tools?.classList.contains('active')) return 'tools';
    return 'graph'; // default
}

// Update Tree View with current JSON-LD data
function updateTreeView(data) {
    const treeContainer = document.getElementById('tree-view-container');
    const breadcrumbContainer = document.getElementById('tree-breadcrumb');
    if (!treeContainer) return;

    // Initialize tree view with imported module
    initTreeView(data, treeContainer, breadcrumbContainer);

    // Expand first level by default
    setTimeout(() => {
        expandToDepth(treeContainer, 1);
    }, 100);
}


// Update Raw JSON View with current JSON-LD data
function updateRawJsonView(data) {
    const rawDisplay = document.getElementById('raw-json-display');
    const copyBtn = document.getElementById('copy-json-btn');

    if (!rawDisplay) return;

    // Initialize raw JSON view with imported module
    initRawJsonView(data, rawDisplay, copyBtn);
}


// ========== Tools Panel Functions ==========

// Toggle loading indicator for tools panel
function toggleToolsLoading(show) {
    if (toolsLoading) {
        toolsLoading.style.display = show ? 'block' : 'none';
    }
}

// Display tool operation result
function displayToolResult(result) {
    if (!toolsResults || !resultsTitle || !resultsCode) return;

    // Update result title based on operation
    const operationTitles = {
        'expand': '🔍 Expanded JSON-LD',
        'compact': '📦 Compacted JSON-LD',
        'flatten': '🗂️ Flattened JSON-LD',
        'validate': '✅ Validation Result'
    };
    resultsTitle.textContent = operationTitles[result.operation] || 'Processing Result';

    // Update stats section
    if (resultsStats) {
        if (result.operation === 'validate') {
            // Display validation stats
            const validIcon = result.valid ? '✅' : '❌';
            const validText = result.valid ? 'Valid' : 'Invalid';
            const errorCount = result.errors ? result.errors.length : 0;
            const warningCount = result.warnings ? result.warnings.length : 0;

            resultsStats.innerHTML = `
                <div class="stat-badge ${result.valid ? 'stat-success' : 'stat-error'}">
                    ${validIcon} ${validText}
                </div>
                <div class="stat-badge stat-error">
                    ${errorCount} Error${errorCount !== 1 ? 's' : ''}
                </div>
                <div class="stat-badge stat-warning">
                    ${warningCount} Warning${warningCount !== 1 ? 's' : ''}
                </div>
            `;
        } else if (result.success && result.result) {
            // Display document stats
            const stats = getDocumentStats(result.result);
            resultsStats.innerHTML = `
                <div class="stat-badge stat-info">
                    ${stats.nodeCount} Node${stats.nodeCount !== 1 ? 's' : ''}
                </div>
                <div class="stat-badge stat-info">
                    ${stats.propertyCount} Propert${stats.propertyCount !== 1 ? 'ies' : 'y'}
                </div>
                <div class="stat-badge stat-info">
                    ${stats.typeCount} Type${stats.typeCount !== 1 ? 's' : ''}
                </div>
            `;
        } else {
            resultsStats.innerHTML = '';
        }
    }

    // Format and display result JSON
    if (result.success) {
        if (result.operation === 'validate') {
            // Display validation details
            const validationDetails = {
                valid: result.valid,
                errors: result.errors || [],
                warnings: result.warnings || [],
                timestamp: result.timestamp
            };
            resultsCode.textContent = JSON.stringify(validationDetails, null, 2);
        } else {
            resultsCode.textContent = JSON.stringify(result.result, null, 2);
        }
    } else {
        // Display error
        resultsCode.textContent = JSON.stringify({
            error: result.error,
            operation: result.operation,
            timestamp: result.timestamp
        }, null, 2);
    }

    // Show results panel
    toolsResults.style.display = 'block';
}

// Clear tools results panel
function clearToolsResults() {
    if (toolsResults) {
        toolsResults.style.display = 'none';
    }
    if (resultsStats) {
        resultsStats.innerHTML = '';
    }
    if (resultsCode) {
        resultsCode.textContent = '';
    }
}

// Handle expand context operation
async function handleExpandContext() {
    if (!jsonldData) {
        showMessage('먼저 JSON-LD 문서를 로드해주세요.', true);
        return;
    }

    try {
        toggleToolsLoading(true);
        const result = await expandContext(jsonldData);
        toggleToolsLoading(false);
        displayToolResult(result);

        if (result.success) {
            showMessage('✅ JSON-LD 확장 완료');
        } else {
            showMessage(`❌ 확장 실패: ${result.error}`, true);
        }
    } catch (error) {
        toggleToolsLoading(false);
        showMessage(`❌ 오류: ${error.message}`, true);
    }
}

// Handle compact context operation
async function handleCompactContext() {
    if (!jsonldData) {
        showMessage('먼저 JSON-LD 문서를 로드해주세요.', true);
        return;
    }

    try {
        toggleToolsLoading(true);
        const result = await compactContext(jsonldData);
        toggleToolsLoading(false);
        displayToolResult(result);

        if (result.success) {
            showMessage('✅ JSON-LD 압축 완료');
        } else {
            showMessage(`❌ 압축 실패: ${result.error}`, true);
        }
    } catch (error) {
        toggleToolsLoading(false);
        showMessage(`❌ 오류: ${error.message}`, true);
    }
}

// Handle flatten operation
async function handleFlattenJsonLd() {
    if (!jsonldData) {
        showMessage('먼저 JSON-LD 문서를 로드해주세요.', true);
        return;
    }

    try {
        toggleToolsLoading(true);
        const result = await flattenJsonLd(jsonldData);
        toggleToolsLoading(false);
        displayToolResult(result);

        if (result.success) {
            showMessage('✅ JSON-LD 평탄화 완료');
        } else {
            showMessage(`❌ 평탄화 실패: ${result.error}`, true);
        }
    } catch (error) {
        toggleToolsLoading(false);
        showMessage(`❌ 오류: ${error.message}`, true);
    }
}

// Handle validate operation
async function handleValidateJsonLd() {
    if (!jsonldData) {
        showMessage('먼저 JSON-LD 문서를 로드해주세요.', true);
        return;
    }

    try {
        toggleToolsLoading(true);
        const result = await validateJsonLd(jsonldData);
        toggleToolsLoading(false);
        displayToolResult(result);

        if (result.success && result.valid) {
            showMessage('✅ JSON-LD 검증 성공');
        } else if (result.success && !result.valid) {
            const errorCount = result.errors ? result.errors.length : 0;
            showMessage(`⚠️ 검증 완료: ${errorCount}개의 오류 발견`, true);
        } else {
            showMessage(`❌ 검증 실패: ${result.error}`, true);
        }
    } catch (error) {
        toggleToolsLoading(false);
        showMessage(`❌ 오류: ${error.message}`, true);
    }
}


// Parse and visualize JSON-LD
async function handleVisualize() {
    try {
        const input = jsonldInput.value.trim();
        if (!input) {
            showMessage('JSON-LD 문서를 입력해주세요.', true);
            return;
        }
        jsonldData = JSON.parse(input);
        nodesDataset.clear();
        edgesDataset.clear();
        expandedNodes.clear();
        expandedMapping = {};
        await processJsonLd(jsonldData);

        // Populate filter dropdowns after data is loaded
        populateFilterDropdowns();

        recenterBtn.click();
        showMessage('JSON-LD 문서 시각화 완료');
    } catch (error) {
        console.error('Error processing JSON-LD:', error);
        showMessage(`오류: ${error.message}`, true);
    }
}


// To prevent long node labels
function truncateText(text, maxLength = 80) {
    if (typeof text !== 'string') return String(text);
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength - 3) + '...';
}


// Process a single JSON-LD node
async function processNode(node, parentId, nodeId) {
    if (typeof node === 'string' && node.startsWith('@')) return;
    if (typeof node === 'object' && node !== null) {
        let nodeLabel = '';
        let url = null;
        if (node['@id']) {
            nodeLabel = node['@id'];
            url = node['@id'];
        } else if (node['@value']) {
            nodeLabel = String(node['@value']);
        } else {
            nodeLabel = "Blank Node";
        }
        if (node['@type']) {
            const types = Array.isArray(node['@type']) ? node['@type'] : [node['@type']];
            nodeLabel += "\nType: " + types.map(t => shortenUri(t)).join(', ');
        }

        // Truncate long labels
        nodeLabel = truncateText(nodeLabel, 80);

        const hasNestedNodes = Object.keys(node).some(k =>
            !['@id', '@type', '@value', '@language'].includes(k)
        );
        nodesDataset.add({
            id: nodeId,
            label: nodeLabel,
            url: url,
            expandable: hasNestedNodes,
            color: !node['@id'] ? '#f39c12' : '#3498db',
            borderWidth: hasNestedNodes ? 2 : 1,
            shapeProperties: {borderDashes: hasNestedNodes ? [5, 5] : false},
            raw: node
        });
        if (parentId) {
            edgesDataset.add({from: parentId, to: nodeId});
        }
    }
}


// Animate layout changes by enabling physics temporarily
function animateLayout() {
    network.setOptions({physics: {enabled: true}});
    network.once("stabilized", () => {
        network.setOptions({physics: {enabled: false}});
    });
}


// Recenter the graph
function recenterGraph() {
    if (network) {
        network.fit();
    }
}


// Expand a node to show its children
async function expandNode(node) {
    const nodeId = node.id;
    expandedNodes.add(nodeId);
    nodesDataset.update({
        id: nodeId,
        borderWidth: 2,
        shapeProperties: {borderDashes: [5, 5]}
    });
    expandedMapping[nodeId] = [];
    if (node.isPredicate) {
        const values = Array.isArray(node.raw) ? node.raw : [node.raw];
        for (let i = 0; i < values.length; i++) {
            const valId = `${nodeId}_val_${i}`;
            const val = values[i];
            if (typeof val === 'object' && val !== null) {
                await processNode(val, nodeId, valId);
            } else {
                const isUrl = typeof val === 'string' && (val.startsWith('http://') || val.startsWith('https://'));

                // Truncate long values
                const displayVal = truncateText(String(val), 80);

                nodesDataset.add({
                    id: valId,
                    label: displayVal,
                    url: isUrl ? val : null,
                    color: isUrl ? '#2ecc71' : '#3498db'
                });
                edgesDataset.add({from: nodeId, to: valId});
            }
            expandedMapping[nodeId].push(valId);
        }
    } else {
        const rawNode = node.raw;
        for (const key in rawNode) {
            if (['@id', '@type', '@value', '@language'].includes(key)) continue;
            const predId = `${nodeId}_pred_${key}`;
            const predLabel = shortenUri(key);
            const objects = rawNode[key];
            const predicateExpandable = Array.isArray(objects) && objects.length > 0;
            nodesDataset.add({
                id: predId,
                label: predLabel,
                color: '#9b59b6',
                font: {color: 'white'},
                raw: objects,
                isPredicate: true,
                expandable: predicateExpandable,
                borderWidth: predicateExpandable ? 2 : 1,
                shapeProperties: {borderDashes: predicateExpandable ? [5, 5] : false}
            });
            edgesDataset.add({from: nodeId, to: predId});
            expandedMapping[nodeId].push(predId);
        }
    }
    animateLayout();
}


// Collapse a node by recursively removing its children
function collapseNode(nodeId) {
    if (expandedMapping[nodeId]) {
        expandedMapping[nodeId].forEach(childId => {
            if (expandedNodes.has(childId)) {
                collapseNode(childId);
            }
            nodesDataset.remove({id: childId});
            edgesDataset.forEach(edge => {
                if (edge.from === nodeId && edge.to === childId) {
                    edgesDataset.remove(edge.id);
                }
            });
        });
        delete expandedMapping[nodeId];
    }
    expandedNodes.delete(nodeId);
    nodesDataset.update({
        id: nodeId,
        borderWidth: 2,
        shapeProperties: {borderDashes: [5, 5]}
    });
    animateLayout();
}


// Process JSON-LD and create the graph
async function processJsonLd(data) {
    const expanded = await jsonld.expand(data);
    if (Array.isArray(expanded)) {
        for (let i = 0; i < expanded.length; i++) {
            await processNode(expanded[i], null, `root_${i}`);
            const rootNode = nodesDataset.get(`root_${i}`);
            if (rootNode && rootNode.expandable) {
                expandNode(rootNode);
            }
        }
    } else {
        await processNode(expanded, null, 'root');
        const rootNode = nodesDataset.get('root');
        if (rootNode && rootNode.expandable) {
            expandNode(rootNode);
        }
    }
    animateLayout();
}


// Shorten URI for display
function shortenUri(uri) {
    if (typeof uri !== 'string') return String(uri);
    if (!uri.startsWith('http://') && !uri.startsWith('https://')) return uri;
    const parts = uri.split(/[/#]/);
    return parts[parts.length - 1] || uri;
}


// Initialize the network
function initNetwork() {
    const data = {
        nodes: nodesDataset,
        edges: edgesDataset
    };
    network = new vis.Network(graphContainer, data, options);

    graphContainer.style.cursor = 'grab';
    network.on('click', function (params) {
        if (params.nodes.length > 0) {
            const nodeId = params.nodes[0];
            const node = nodesDataset.get(nodeId);

            if (node.expandable) {
                if (expandedNodes.has(nodeId)) {
                    collapseNode(nodeId);
                } else {
                    expandNode(node);
                }
            }
        }
    });

    network.on('hoverNode', function (params) {
        const node = nodesDataset.get(params.node);
        if (node && node.expandable) {
            graphContainer.style.cursor = 'pointer';
        }
    });

    network.on('blurNode', function (params) {
        graphContainer.style.cursor = 'grab';
    });

    network.on('dragStart', function () {
        graphContainer.style.cursor = 'grabbing';
    });

    network.on('dragEnd', function () {
        graphContainer.style.cursor = 'grab';
    });
}


// Display success/error message
function showMessage(message, isError = false) {
    messageBox.textContent = message;
    messageBox.style.display = 'block';
    if (isError) {
        messageBox.classList.add('error');
    } else {
        messageBox.classList.remove('error');
    }
    setTimeout(() => {
        messageBox.style.display = 'none';
    }, 5000);
}


// Expand one level of expandable nodes
function expandLevel() {
    // Visible nodes that are expandable but not yet expanded
    const nodes = nodesDataset.get({
        filter: function (node) {
            return node.expandable && !expandedNodes.has(node.id);
        }
    });

    for (const node of nodes) {
        expandNode(node);
    }
}


// Collapse one level of expanded nodes
function collapseLevel() {
    const expandedNodeIds = Array.from(expandedNodes);

    // Find leaf nodes (nodes that are expanded but don't have expanded children)
    const leafNodes = [];
    for (const nodeId of expandedNodeIds) {
        // Check if this node has any expanded children
        const hasExpandedChildren = expandedMapping[nodeId] &&
            expandedMapping[nodeId].some(childId => expandedNodes.has(childId));

        if (!hasExpandedChildren) {
            leafNodes.push(nodeId);
        }
    }

    // Collapse only the leaf nodes
    for (const nodeId of leafNodes) {
        collapseNode(nodeId);
    }
}


// Load example JSON-LD document
function loadExample() {
    const example = {
        "@context": {
            "snomed": "http://snomed.info/id/",
            "custom": "https://example.org/generations-study/custom-ontology#",
            "side": {
                "@id": "snomed:384727002",
                "@type": "@vocab",
                "@context": {
                    "L": "snomed:7771000",
                    "R": "snomed:24028007"
                }
            },
            "screenDetected": {
                "@id": "snomed:171176006",
                "@type": "@vocab",
                "@context": {
                    "Y": "snomed:373066001",
                    "N": "snomed:373067005"
                }
            }
        },
        "@graph": [
            {
                "fileName": "01.txt",
                "side": "L",
                "screenDetected": "Y"
            },
            {
                "fileName": "02.txt",
                "side": "R",
                "screenDetected": "N"
            }
        ]
    };

    jsonldInput.value = JSON.stringify(example, null, 2);
}


function clearAll() {
    jsonldInput.value = '';
    nodesDataset.clear();
    edgesDataset.clear();
    expandedNodes.clear();
    expandedMapping = {};
    jsonldData = null;
    ontologyInfo.classList.remove('show');
}

// ==========================================
// Search and Filter Functions
// ==========================================

/**
 * Execute search on current JSON-LD data
 */
function executeSearch() {
    if (!jsonldData) {
        showMessage('먼저 JSON-LD 문서를 로드해주세요.', true);
        return;
    }

    const searchTerm = searchInput.value.trim();
    if (!searchTerm) {
        showMessage('검색어를 입력해주세요.', true);
        return;
    }

    try {
        // Clear previous search highlights
        clearSearchHighlights();

        // Get search options
        const options = {
            caseSensitive: caseSensitiveCheck.checked,
            exactMatch: exactMatchCheck.checked,
            searchIn: []
        };

        if (searchKeysCheck.checked) options.searchIn.push('keys');
        if (searchValuesCheck.checked) options.searchIn.push('values');
        if (searchTypesCheck.checked) options.searchIn.push('types');

        if (options.searchIn.length === 0) {
            showMessage('최소 하나의 검색 범위를 선택해주세요.', true);
            return;
        }

        // Perform search
        searchResults = searchJsonLd(jsonldData, searchTerm, options);

        if (searchResults.length === 0) {
            showMessage(`"${searchTerm}"에 대한 검색 결과가 없습니다.`, true);
            searchResultsPanel.style.display = 'none';
            return;
        }

        // Get search statistics
        const stats = getSearchStats(searchResults);

        // Display results panel
        searchResultsPanel.style.display = 'block';
        searchResultsTitle.textContent = `검색 결과: "${searchTerm}"`;

        // Display statistics
        searchResultsStats.innerHTML = `
            <div class="stat-badge stat-info">총 ${stats.total}개 결과</div>
            <div class="stat-badge">키: ${stats.byType.key || 0}개</div>
            <div class="stat-badge">값: ${stats.byType.value || 0}개</div>
            <div class="stat-badge">타입: ${stats.byType.type || 0}개</div>
        `;

        // Highlight matching nodes in graph
        highlightSearchResults(searchTerm, options.caseSensitive);

        // Display results list
        displaySearchResultsList();

        // Initialize navigation
        currentSearchIndex = 0;
        updateSearchNavigation();

        // Focus on first result
        if (searchResults.length > 0) {
            focusOnSearchResult(0);
        }

        showMessage(`✅ ${searchResults.length}개의 검색 결과를 찾았습니다.`);

    } catch (error) {
        console.error('Search error:', error);
        showMessage(`❌ 검색 중 오류 발생: ${error.message}`, true);
    }
}

/**
 * Clear search results and highlights
 */
function clearSearch() {
    searchInput.value = '';
    searchResults = [];
    currentSearchIndex = -1;

    clearSearchHighlights();

    searchResultsPanel.style.display = 'none';
    searchResultsStats.innerHTML = '';
    searchResultsList.innerHTML = '';

    showMessage('검색 결과가 초기화되었습니다.');
}

/**
 * Highlight nodes matching search results
 */
function highlightSearchResults(searchTerm, caseSensitive) {
    const nodes = nodesDataset.get();

    nodes.forEach(node => {
        const label = node.label || '';
        const url = node.url || '';

        // Check if node label or URL matches search term
        const searchStr = caseSensitive ? searchTerm : searchTerm.toLowerCase();
        const labelStr = caseSensitive ? label : label.toLowerCase();
        const urlStr = caseSensitive ? url : url.toLowerCase();

        if (labelStr.includes(searchStr) || urlStr.includes(searchStr)) {
            // Store original properties
            highlightedNodes.add(node.id);

            // Highlight node
            nodesDataset.update({
                id: node.id,
                color: {
                    background: '#fff3cd',
                    border: '#ff6b6b'
                },
                borderWidth: 3,
                shadow: {
                    enabled: true,
                    color: 'rgba(255, 107, 107, 0.5)',
                    size: 10
                }
            });
        }
    });
}

/**
 * Clear all search highlights
 */
function clearSearchHighlights() {
    highlightedNodes.forEach(nodeId => {
        const node = nodesDataset.get(nodeId);
        if (node) {
            // Restore original color
            const originalColor = !node.url ? '#f39c12' : '#3498db';

            nodesDataset.update({
                id: nodeId,
                color: originalColor,
                borderWidth: node.expandable ? 2 : 1,
                shadow: {
                    enabled: false
                }
            });
        }
    });

    highlightedNodes.clear();
}

/**
 * Display search results list
 */
function displaySearchResultsList() {
    searchResultsList.innerHTML = '';

    searchResults.forEach((result, index) => {
        const resultItem = document.createElement('div');
        resultItem.className = 'search-result-item';
        resultItem.innerHTML = `
            <div class="result-type">${result.type}</div>
            <div class="result-path">${result.path}</div>
            <div class="result-key">${result.key}</div>
            <div class="result-value">${JSON.stringify(result.value).substring(0, 100)}</div>
        `;

        resultItem.addEventListener('click', () => {
            currentSearchIndex = index;
            updateSearchNavigation();
            focusOnSearchResult(index);
        });

        searchResultsList.appendChild(resultItem);
    });
}

/**
 * Navigate to previous search result
 */
function navigateToPrevResult() {
    if (searchResults.length === 0) return;

    currentSearchIndex--;
    if (currentSearchIndex < 0) {
        currentSearchIndex = searchResults.length - 1;
    }

    updateSearchNavigation();
    focusOnSearchResult(currentSearchIndex);
}

/**
 * Navigate to next search result
 */
function navigateToNextResult() {
    if (searchResults.length === 0) return;

    currentSearchIndex++;
    if (currentSearchIndex >= searchResults.length) {
        currentSearchIndex = 0;
    }

    updateSearchNavigation();
    focusOnSearchResult(currentSearchIndex);
}

/**
 * Update search navigation UI
 */
function updateSearchNavigation() {
    if (searchResults.length === 0) {
        resultCounter.textContent = '0 / 0';
        prevResultBtn.disabled = true;
        nextResultBtn.disabled = true;
        return;
    }

    resultCounter.textContent = `${currentSearchIndex + 1} / ${searchResults.length}`;
    prevResultBtn.disabled = false;
    nextResultBtn.disabled = false;

    // Highlight current result in list
    const items = searchResultsList.querySelectorAll('.search-result-item');
    items.forEach((item, index) => {
        if (index === currentSearchIndex) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
}

/**
 * Focus on specific search result in graph
 */
function focusOnSearchResult(index) {
    if (!searchResults[index]) return;

    const result = searchResults[index];

    // Try to find node by path
    // This is a simplified approach - may need refinement based on actual node structure
    const nodes = nodesDataset.get();

    // Find node that matches the search result context
    for (const node of nodes) {
        if (node.raw && JSON.stringify(node.raw).includes(JSON.stringify(result.value))) {
            // Focus on this node
            if (network) {
                network.focus(node.id, {
                    scale: 1.5,
                    animation: {
                        duration: 500,
                        easingFunction: 'easeInOutQuad'
                    }
                });

                // Temporarily highlight focused node
                const originalColor = node.color;
                nodesDataset.update({
                    id: node.id,
                    color: {
                        background: '#ffd700',
                        border: '#ff4500'
                    }
                });

                // Restore highlight after 1 second
                setTimeout(() => {
                    nodesDataset.update({
                        id: node.id,
                        color: {
                            background: '#fff3cd',
                            border: '#ff6b6b'
                        }
                    });
                }, 1000);
            }
            break;
        }
    }
}

/**
 * Close search results panel
 */
function closeSearchResults() {
    searchResultsPanel.style.display = 'none';
}

/**
 * Apply type and property filters
 */
function applyFilters() {
    if (!jsonldData) {
        showMessage('먼저 JSON-LD 문서를 로드해주세요.', true);
        return;
    }

    try {
        // Get selected types
        const selectedTypes = Array.from(typeFilter.selectedOptions).map(opt => opt.value);

        // Get selected properties
        const selectedProperties = Array.from(propertyFilter.selectedOptions).map(opt => opt.value);

        if (selectedTypes.length === 0 && selectedProperties.length === 0) {
            showMessage('최소 하나의 필터를 선택해주세요.', true);
            return;
        }

        // Clear previous highlights
        clearSearchHighlights();

        let matchingNodes = [];

        // Filter by type
        if (selectedTypes.length > 0) {
            const typeResults = filterByType(jsonldData, selectedTypes);
            matchingNodes = [...matchingNodes, ...typeResults];
        }

        // Filter by property
        if (selectedProperties.length > 0) {
            const propResults = filterByProperty(jsonldData, selectedProperties);
            matchingNodes = [...matchingNodes, ...propResults];
        }

        if (matchingNodes.length === 0) {
            showMessage('선택한 필터와 일치하는 노드가 없습니다.', true);
            return;
        }

        // Highlight matching nodes
        const nodes = nodesDataset.get();
        matchingNodes.forEach(match => {
            // Find nodes that contain this data
            nodes.forEach(node => {
                if (node.raw && JSON.stringify(node.raw).includes(JSON.stringify(match.node))) {
                    highlightedNodes.add(node.id);

                    nodesDataset.update({
                        id: node.id,
                        color: {
                            background: '#d1ecf1',
                            border: '#0c5460'
                        },
                        borderWidth: 3,
                        shadow: {
                            enabled: true,
                            color: 'rgba(12, 84, 96, 0.5)',
                            size: 10
                        }
                    });
                }
            });
        });

        showMessage(`✅ ${highlightedNodes.size}개의 노드가 필터와 일치합니다.`);

    } catch (error) {
        console.error('Filter error:', error);
        showMessage(`❌ 필터 적용 중 오류 발생: ${error.message}`, true);
    }
}

/**
 * Clear all filters
 */
function clearFilters() {
    typeFilter.selectedIndex = -1;
    propertyFilter.selectedIndex = -1;

    clearSearchHighlights();

    showMessage('필터가 초기화되었습니다.');
}

/**
 * Populate filter dropdowns with available types and properties
 */
function populateFilterDropdowns() {
    if (!jsonldData) return;

    try {
        // Get all types and properties
        const types = getAllTypes(jsonldData);
        const properties = getAllProperties(jsonldData);

        // Populate type filter
        typeFilter.innerHTML = '';
        types.forEach(type => {
            const option = document.createElement('option');
            option.value = type;
            option.textContent = type;
            typeFilter.appendChild(option);
        });

        // Populate property filter
        propertyFilter.innerHTML = '';
        properties.forEach(prop => {
            const option = document.createElement('option');
            option.value = prop;
            option.textContent = prop;
            propertyFilter.appendChild(option);
        });

    } catch (error) {
        console.error('Error populating filters:', error);
    }
}


document.addEventListener('DOMContentLoaded', initApp);
