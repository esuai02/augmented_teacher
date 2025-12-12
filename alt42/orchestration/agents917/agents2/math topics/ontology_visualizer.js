/**
 * OWL 온톨로지 시각화 JavaScript
 * D3.js를 사용한 인터랙티브 네트워크 그래프
 */

(function() {
    'use strict';

    // 전역 변수
    let svg, g, simulation;
    let nodes, links;
    let nodeElements, linkElements, labelElements;
    let showLabels = true;
    let currentLayout = 'force';
    let selectedNodes = []; // 선택된 노드들 (최대 2개)
    let lastClickTime = 0; // 더블클릭 감지를 위한 마지막 클릭 시간
    let clickTimer = null; // 클릭 이벤트 지연 실행을 위한 타이머
    
    // SVG 설정
    const width = Math.max(800, window.innerWidth - 40);
    const height = Math.max(600, window.innerHeight - 300);

    // 색상 스케일 (stage별)
    // D3.js v7 호환성: schemeCategory10 사용 (v7에서는 schemeCategory20이 없음)
    let colorRange = [];
    try {
        // D3.js v7에서는 schemeCategory10 사용
        if (d3.schemeCategory10 && Array.isArray(d3.schemeCategory10)) {
            colorRange = d3.schemeCategory10.slice();
        }
        // schemeSet3가 배열인 경우 추가
        if (d3.schemeSet3) {
            if (Array.isArray(d3.schemeSet3)) {
                colorRange = colorRange.concat(d3.schemeSet3);
            } else if (typeof d3.schemeSet3 === 'object' && d3.schemeSet3 !== null) {
                // 객체인 경우 값들을 배열로 변환 (안전하게)
                try {
                    const values = Object.values(d3.schemeSet3);
                    const flatColors = values.reduce((acc, val) => {
                        if (Array.isArray(val)) {
                            return acc.concat(val);
                        } else if (typeof val === 'string') {
                            return acc.concat([val]);
                        }
                        return acc;
                    }, []);
                    if (flatColors.length > 0) {
                        colorRange = colorRange.concat(flatColors);
                    }
                } catch (e) {
                    console.warn('schemeSet3 변환 실패:', e);
                }
            }
        }
    } catch (e) {
        console.warn('색상 스키마 로드 실패:', e);
    }
    
    // 기본 색상 (색상 스키마가 없는 경우)
    if (colorRange.length === 0) {
        colorRange = ['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf', 
                     '#aec7e8', '#ffbb78', '#98df8a', '#ff9896', '#c5b0d5', '#c49c94', '#f7b6d3', '#c7c7c7', '#dbdb8d', '#9edae5',
                     '#ff6b6b', '#4ecdc4', '#45b7d1', '#f9ca24', '#f0932b', '#eb4d4b', '#6c5ce7', '#a29bfe', '#00b894', '#00cec9'];
    }
    
    const colorScale = d3.scaleOrdinal()
        .domain([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28])
        .range(colorRange);

    // 초기화
    function init() {
        console.log('시각화 초기화 시작...');
        
        if (!window.graphData) {
            console.error('graphData가 정의되지 않았습니다.');
            console.error('window.graphData:', window.graphData);
            return;
        }
        
        console.log('graphData 로드됨:', {
            nodes: graphData.nodes ? graphData.nodes.length : 0,
            links: graphData.links ? graphData.links.length : 0,
            metadata: graphData.metadata
        });
        
        // 링크 타입별 통계
        if (graphData.links) {
            const linkTypes = {};
            graphData.links.forEach(link => {
                const type = link.type || 'unknown';
                linkTypes[type] = (linkTypes[type] || 0) + 1;
            });
            console.log('링크 타입별 통계:', linkTypes);
        }
        
        if (!graphData.nodes || graphData.nodes.length === 0) {
            console.error('노드 데이터가 없습니다.');
            return;
        }

        // 데이터 준비
        nodes = graphData.nodes.map(d => ({ ...d }));
        
        // 링크 데이터 준비 및 유효성 검사
        links = graphData.links.map(d => {
            const sourceId = typeof d.source === 'string' ? d.source : (d.source?.id || d.source);
            const targetId = typeof d.target === 'string' ? d.target : (d.target?.id || d.target);
            return {
                ...d,
                source: sourceId,
                target: targetId
            };
        }).filter(link => link.source && link.target); // 유효하지 않은 링크 제거

        // 노드 ID로 인덱스 매핑 (D3.js force 시뮬레이션용)
        const nodeMap = new Map(nodes.map((d, i) => [d.id, i]));
        const validLinks = [];
        
        links.forEach(link => {
            const sourceIdx = nodeMap.get(link.source);
            const targetIdx = nodeMap.get(link.target);
            
            // 두 노드가 모두 존재하는 경우만 추가
            if (sourceIdx !== undefined && targetIdx !== undefined) {
                validLinks.push({
                    ...link,
                    source: sourceIdx,
                    target: targetIdx
                });
            } else {
                console.warn('유효하지 않은 링크 제거:', link.source, '->', link.target);
            }
        });
        
        links = validLinks;
        
        // 최종 링크 통계 출력
        const finalLinkTypes = {};
        links.forEach(link => {
            const type = link.type || 'unknown';
            finalLinkTypes[type] = (finalLinkTypes[type] || 0) + 1;
        });
        console.log('렌더링할 링크 타입별 통계:', finalLinkTypes);
        console.log('총 렌더링 링크 수:', links.length);

        // SVG 생성
        const container = d3.select('#graph-container');
        container.selectAll('*').remove(); // 기존 내용 제거
        
        svg = container
            .append('svg')
            .attr('width', width)
            .attr('height', height)
            .style('border', '1px solid #ddd');

        // 줌/팬 기능
        const zoom = d3.zoom()
            .scaleExtent([0.1, 4])
            .on('zoom', (event) => {
                g.attr('transform', event.transform);
            });

        svg.call(zoom);

        // 그룹 생성
        g = svg.append('g');

        // 시뮬레이션 생성
        createSimulation();

        // 그래프 그리기
        drawGraph();

        // 범례 업데이트
        updateLegend();

        // 이벤트 리스너
        setupEventListeners();
    }

    // Force 시뮬레이션 생성
    function createSimulation() {
        // 링크를 노드 객체 참조로 변환
        const linksWithNodes = links.map(link => ({
            ...link,
            source: nodes[link.source],
            target: nodes[link.target]
        }));
        
        simulation = d3.forceSimulation(nodes)
            .force('link', d3.forceLink(linksWithNodes).id(d => d.id).distance(100))
            .force('charge', d3.forceManyBody().strength(-300))
            .force('center', d3.forceCenter(width / 2, height / 2))
            .force('collision', d3.forceCollide().radius(30))
            .on('tick', ticked);
    }

    // 관계 타입별 이름 정의
    const relationshipTypeNames = {
        'precedes': 'ar:precedes - 동일 단원 내 교과 순서',
        'dependsOn': 'ar:dependsOn - 논리적 선행 개념 관계',
        'refersTo': 'ar:refersTo - 관련 맥락 또는 응용 주제 연결',
        'reinforces': 'ar:reinforces - 복습·보강 관계',
        'contrastsWith': 'ar:contrastsWith - 개념 대조 관계',
        'generalizes': 'ar:generalizes - 하위 주제를 포괄하는 상위 개념',
        'simplifies': 'ar:simplifies - 복잡한 개념의 단순화 버전',
        'appliesTo': 'ar:appliesTo - 개념이 적용되는 실전 주제',
        'coOccursWith': 'ar:coOccursWith - 함께 등장하는 병렬 주제',
        'conflictsWith': 'ar:conflictsWith - 혼동 위험이 높은 개념 관계',
        'revives': 'ar:revives - 잊힌 개념의 재활성화',
        'adjustsFor': 'ar:adjustsFor - 학생 상황 기반 조정 관계',
        'bridgesTo': 'ar:bridgesTo - 다른 학년·단원으로의 연결 다리',
        'confirms': 'ar:confirms - 전 단원의 결과를 검증하는 주제',
        'derivedFrom': 'ar:derivedFrom - 수식적/논리적으로 파생된 관계'
    };

    // 관계 타입별 스타일 정의
    function getLinkStyle(type) {
        const styles = {
            'precedes': { color: '#1f77b4', width: 2, opacity: 0.7, dasharray: 'none', marker: 'blue' },
            'dependsOn': { color: '#ff7f0e', width: 3, opacity: 0.95, dasharray: '8,4', marker: 'orange' },
            'refersTo': { color: '#2ca02c', width: 2, opacity: 0.8, dasharray: '4,4', marker: 'green' },
            'reinforces': { color: '#9467bd', width: 2.5, opacity: 0.85, dasharray: '6,3', marker: 'purple' },
            'contrastsWith': { color: '#d62728', width: 2.5, opacity: 0.9, dasharray: '5,5', marker: 'red' },
            'generalizes': { color: '#8c564b', width: 2.5, opacity: 0.85, dasharray: 'none', marker: 'brown' },
            'simplifies': { color: '#e377c2', width: 2, opacity: 0.8, dasharray: '3,3', marker: 'pink' },
            'appliesTo': { color: '#17becf', width: 2, opacity: 0.8, dasharray: 'none', marker: 'cyan' },
            'coOccursWith': { color: '#bcbd22', width: 2, opacity: 0.75, dasharray: '2,2', marker: 'yellow' },
            'conflictsWith': { color: '#c82333', width: 3, opacity: 0.95, dasharray: '10,5', marker: 'darkred' },
            'revives': { color: '#aec7e8', width: 2, opacity: 0.7, dasharray: 'none', marker: 'lightblue' },
            'adjustsFor': { color: '#ffbb78', width: 2, opacity: 0.75, dasharray: '4,4', marker: 'lightorange' },
            'bridgesTo': { color: '#98df8a', width: 2.5, opacity: 0.85, dasharray: '6,3', marker: 'lightgreen' },
            'confirms': { color: '#c5b0d5', width: 2, opacity: 0.8, dasharray: 'none', marker: 'lightpurple' },
            'derivedFrom': { color: '#c49c94', width: 2.5, opacity: 0.85, dasharray: 'none', marker: 'lightbrown' }
        };
        return styles[type] || { color: '#888', width: 2, opacity: 0.8, dasharray: 'none', marker: 'default' };
    }

    // 범례 동적 생성
    function updateLegend() {
        const legendContainer = document.querySelector('.legend .legend-items');
        if (!legendContainer) return;
        
        // 실제 사용된 관계 타입만 수집
        const usedTypes = new Set();
        links.forEach(link => {
            if (link.type) {
                usedTypes.add(link.type);
            }
        });
        
        // 기존 범례 제거
        legendContainer.innerHTML = '';
        
        // 사용된 관계 타입별로 범례 항목 생성
        Array.from(usedTypes).sort().forEach(type => {
            const style = getLinkStyle(type);
            const name = relationshipTypeNames[type] || type;
            
            const legendItem = document.createElement('div');
            legendItem.className = 'legend-item';
            legendItem.innerHTML = `
                <span class="legend-color" style="background: ${style.color};"></span>
                <span>${name}</span>
            `;
            legendContainer.appendChild(legendItem);
        });
        
        // 사용된 관계 타입이 없으면 메시지 표시
        if (usedTypes.size === 0) {
            legendContainer.innerHTML = '<div style="color: #999; font-style: italic;">사용된 관계 타입이 없습니다.</div>';
        }
    }

    // 그래프 그리기
    function drawGraph() {
        console.log('그래프 그리기 시작. 링크 수:', links.length);
        
        // 링크 타입별 개수 확인
        const linkTypeCounts = {};
        links.forEach(l => {
            const type = l.type || 'unknown';
            linkTypeCounts[type] = (linkTypeCounts[type] || 0) + 1;
        });
        console.log('링크 타입별 통계:', linkTypeCounts);
        
        // 화살표 마커 정의 (모든 관계 타입에 대한 마커 생성)
        const defs = svg.append('defs');
        const markerColors = {
            'blue': '#1f77b4',
            'orange': '#ff7f0e',
            'green': '#2ca02c',
            'purple': '#9467bd',
            'red': '#d62728',
            'brown': '#8c564b',
            'pink': '#e377c2',
            'cyan': '#17becf',
            'yellow': '#bcbd22',
            'darkred': '#c82333',
            'lightblue': '#aec7e8',
            'lightorange': '#ffbb78',
            'lightgreen': '#98df8a',
            'lightpurple': '#c5b0d5',
            'lightbrown': '#c49c94',
            'default': '#888'
        };
        
        Object.keys(markerColors).forEach(colorName => {
            const marker = defs.append('marker')
                .attr('id', `arrowhead-${colorName}`)
                .attr('viewBox', '0 -5 10 10')
                .attr('refX', 25)
                .attr('refY', 0)
                .attr('markerWidth', 6)
                .attr('markerHeight', 6)
                .attr('orient', 'auto');
            
            marker.append('path')
                .attr('d', 'M0,-5L10,0L0,5')
                .attr('fill', markerColors[colorName]);
        });
        
        // 링크 그리기
        linkElements = g.append('g')
            .attr('class', 'links')
            .selectAll('line')
            .data(links)
            .enter()
            .append('line')
            .attr('stroke', d => {
                const style = getLinkStyle(d.type);
                return style.color;
            })
            .attr('stroke-width', d => {
                const style = getLinkStyle(d.type);
                return Math.max(style.width, Math.sqrt((d.value || 1)) * 2);
            })
            .attr('opacity', d => {
                const style = getLinkStyle(d.type);
                return style.opacity;
            })
            .style('stroke-dasharray', d => {
                const style = getLinkStyle(d.type);
                return style.dasharray;
            })
            .style('stroke-width', d => {
                const style = getLinkStyle(d.type);
                return Math.max(style.width, Math.sqrt((d.value || 1)) * 2);
            })
            .style('stroke-opacity', d => {
                const style = getLinkStyle(d.type);
                return style.opacity;
            })
            .attr('marker-end', d => {
                const style = getLinkStyle(d.type);
                return `url(#arrowhead-${style.marker})`;
            });

        // 노드 그리기
        nodeElements = g.append('g')
            .attr('class', 'nodes')
            .selectAll('circle')
            .data(nodes)
            .enter()
            .append('circle')
            .attr('r', 8)
            .attr('fill', d => colorScale(d.stage))
            .attr('stroke', d => selectedNodes.some(n => n.id === d.id) ? '#ff0000' : '#fff')
            .attr('stroke-width', d => selectedNodes.some(n => n.id === d.id) ? 4 : 2)
            .call(drag(simulation))
            .on('mouseover', handleMouseOver)
            .on('mouseout', handleMouseOut)
            .on('click', handleNodeClick)
            .on('dblclick', handleNodeDoubleClick);
        
        // 선택 상태 초기화
        selectedNodes = [];

        // 라벨 그리기
        labelElements = g.append('g')
            .attr('class', 'labels')
            .selectAll('text')
            .data(nodes)
            .enter()
            .append('text')
            .text(d => d.label)
            .attr('font-size', '10px')
            .attr('dx', 12)
            .attr('dy', 4)
            .attr('fill', '#333')
            .style('pointer-events', 'none')
            .style('opacity', showLabels ? 1 : 0);
    }

    // 드래그 함수
    function drag(simulation) {
        function dragstarted(event) {
            if (!event.active) simulation.alphaTarget(0.3).restart();
            event.subject.fx = event.subject.x;
            event.subject.fy = event.subject.y;
        }

        function dragged(event) {
            event.subject.fx = event.x;
            event.subject.fy = event.y;
        }

        function dragended(event) {
            if (!event.active) simulation.alphaTarget(0);
            event.subject.fx = null;
            event.subject.fy = null;
        }

        return d3.drag()
            .on('start', dragstarted)
            .on('drag', dragged)
            .on('end', dragended);
    }

    // 틱 함수 (애니메이션 업데이트)
    function ticked() {
        // 링크 좌표 업데이트
        linkElements
            .attr('x1', d => {
                const source = typeof d.source === 'object' ? d.source : nodes[d.source];
                return source ? source.x : 0;
            })
            .attr('y1', d => {
                const source = typeof d.source === 'object' ? d.source : nodes[d.source];
                return source ? source.y : 0;
            })
            .attr('x2', d => {
                const target = typeof d.target === 'object' ? d.target : nodes[d.target];
                return target ? target.x : 0;
            })
            .attr('y2', d => {
                const target = typeof d.target === 'object' ? d.target : nodes[d.target];
                return target ? target.y : 0;
            });

        // 노드 좌표 업데이트
        nodeElements
            .attr('cx', d => d.x || 0)
            .attr('cy', d => d.y || 0);

        // 라벨 좌표 업데이트
        labelElements
            .attr('x', d => d.x || 0)
            .attr('y', d => d.y || 0);
    }

    // 마우스 오버 핸들러
    function handleMouseOver(event, d) {
        // 툴팁 표시
        const tooltip = d3.select('body').selectAll('.tooltip').data([d]);
        
        tooltip.enter()
            .append('div')
            .attr('class', 'tooltip')
            .merge(tooltip)
            .style('opacity', 1)
            .html(`
                <strong>${d.label}</strong><br/>
                Stage: ${d.stage}<br/>
                ${d.description ? d.description.substring(0, 100) + '...' : ''}
            `)
            .style('left', (event.pageX + 10) + 'px')
            .style('top', (event.pageY - 10) + 'px');

        // 관련 노드 강조
        const relatedNodeIds = new Set([d.id]);
        links.forEach(l => {
            const sourceId = typeof l.source === 'object' ? l.source.id : nodes[l.source]?.id;
            const targetId = typeof l.target === 'object' ? l.target.id : nodes[l.target]?.id;
            if (sourceId === d.id) relatedNodeIds.add(targetId);
            if (targetId === d.id) relatedNodeIds.add(sourceId);
        });

        nodeElements.style('opacity', n => {
            return relatedNodeIds.has(n.id) ? 1 : 0.3;
        });

        linkElements.style('opacity', l => {
            const sourceId = typeof l.source === 'object' ? l.source.id : nodes[l.source]?.id;
            const targetId = typeof l.target === 'object' ? l.target.id : nodes[l.target]?.id;
            if (sourceId === d.id || targetId === d.id) {
                return 1;  // 관련 링크는 완전히 표시
            }
            // dependsOn 관계는 약간 더 밝게 표시
            return l.type === 'dependsOn' ? 0.15 : 0.1;
        });
    }

    // 마우스 아웃 핸들러
    function handleMouseOut() {
        d3.selectAll('.tooltip').style('opacity', 0).remove();
        nodeElements.style('opacity', 1);
        linkElements.style('opacity', 0.6);
    }

    // 노드 더블클릭 핸들러
    function handleNodeDoubleClick(event, d) {
        event.stopPropagation();
        event.preventDefault();
        
        // 클릭 타이머 취소 (더블클릭이면 클릭 이벤트 실행 방지)
        if (clickTimer) {
            clearTimeout(clickTimer);
            clickTimer = null;
        }
        
        // 더블클릭 시 URL 열기
        if (d.url) {
            window.open(d.url, '_blank');
        } else {
            console.log('노드에 URL이 없습니다:', d.label);
        }
    }
    
    // 노드 클릭 핸들러
    function handleNodeClick(event, d) {
        event.stopPropagation();
        
        // Ctrl/Cmd 키를 누르지 않으면 URL 열기 대신 선택 모드
        if (event.ctrlKey || event.metaKey) {
            // Ctrl/Cmd + 클릭: URL 열기
            if (d.url) {
                window.open(d.url, '_blank');
            }
            return;
        }
        
        // 더블클릭 감지: 짧은 시간 내에 두 번 클릭되면 더블클릭으로 간주
        const currentTime = Date.now();
        const timeSinceLastClick = currentTime - lastClickTime;
        lastClickTime = currentTime;
        
        // 더블클릭 가능성이 있으면 클릭 이벤트를 지연 실행
        if (timeSinceLastClick < 300) {
            // 더블클릭 가능성이 있으므로 클릭 이벤트를 취소
            if (clickTimer) {
                clearTimeout(clickTimer);
            }
            clickTimer = null;
            return;
        }
        
        // 클릭 이벤트를 약간 지연시켜 더블클릭과 구분
        clickTimer = setTimeout(() => {
            clickTimer = null;
            
            // 노드 선택/해제
            const index = selectedNodes.findIndex(n => n.id === d.id);
            if (index >= 0) {
                // 이미 선택된 노드면 해제
                selectedNodes.splice(index, 1);
                updateNodeSelection();
            } else {
                // 새 노드 선택
                if (selectedNodes.length >= 2) {
                    // 이미 2개 선택되어 있으면 첫 번째 제거
                    selectedNodes.shift();
                }
                selectedNodes.push(d);
                updateNodeSelection();
                
                // 2개 선택되면 팝업 표시
                if (selectedNodes.length === 2) {
                    showRelationshipPopup(selectedNodes[0], selectedNodes[1]);
                }
            }
        }, 300); // 300ms 지연
    }
    
    function updateNodeSelection() {
        // 선택된 노드 시각적 표시
        if (nodeElements) {
            nodeElements
                .attr('stroke-width', d => {
                    return selectedNodes.some(n => n.id === d.id) ? 4 : 2;
                })
                .attr('stroke', d => {
                    return selectedNodes.some(n => n.id === d.id) ? '#ff0000' : '#fff';
                });
        }
    }
    
    function showRelationshipPopup(node1, node2) {
        // 기존 팝업 제거
        const existingPopup = document.getElementById('relationship-popup');
        if (existingPopup) {
            existingPopup.remove();
        }
        
        // 두 노드 간 기존 관계 확인
        const existingLink = links.find(l => {
            const sourceId = typeof l.source === 'object' ? l.source.id : nodes[l.source]?.id;
            const targetId = typeof l.target === 'object' ? l.target.id : nodes[l.target]?.id;
            return (sourceId === node1.id && targetId === node2.id) ||
                   (sourceId === node2.id && targetId === node1.id);
        });
        
        const existingType = existingLink ? existingLink.type : null;
        const existingDirection = existingLink ? 
            (typeof existingLink.source === 'object' ? existingLink.source.id : nodes[existingLink.source]?.id) === node1.id ? 'forward' : 'reverse'
            : null;
        
        // 팝업 생성
        const popup = document.createElement('div');
        popup.id = 'relationship-popup';
        popup.className = 'relationship-popup';
        
        popup.innerHTML = `
            <div class="popup-content">
                <div class="popup-header">
                    <h3>관계 설정</h3>
                    <button class="popup-close" onclick="closePopup();">×</button>
                </div>
                <div class="popup-body">
                    <div class="node-info">
                        <div class="node-item">
                            <strong>노드 1:</strong> ${node1.label} (Stage ${node1.stage})
                        </div>
                        <div class="node-item">
                            <strong>노드 2:</strong> ${node2.label} (Stage ${node2.stage})
                        </div>
                    </div>
                    <div class="direction-selector">
                        <label>방향:</label>
                        <label><input type="radio" name="direction" value="forward" ${existingDirection === 'forward' || !existingDirection ? 'checked' : ''}> ${node1.label} → ${node2.label}</label>
                        <label><input type="radio" name="direction" value="reverse" ${existingDirection === 'reverse' ? 'checked' : ''}> ${node2.label} → ${node1.label}</label>
                    </div>
                    <div class="relationship-type">
                        <label>관계 타입:</label>
                        <select id="relationship-type-select">
                            <option value="precedes" ${existingType === 'precedes' ? 'selected' : ''}>ar:precedes - 동일 단원 내 교과 순서</option>
                            <option value="dependsOn" ${existingType === 'dependsOn' ? 'selected' : ''}>ar:dependsOn - 논리적 선행 개념 관계</option>
                            <option value="refersTo" ${existingType === 'refersTo' ? 'selected' : ''}>ar:refersTo - 관련 맥락 또는 응용 주제 연결</option>
                            <option value="reinforces" ${existingType === 'reinforces' ? 'selected' : ''}>ar:reinforces - 복습·보강 관계</option>
                            <option value="contrastsWith" ${existingType === 'contrastsWith' ? 'selected' : ''}>ar:contrastsWith - 개념 대조 관계</option>
                            <option value="generalizes" ${existingType === 'generalizes' ? 'selected' : ''}>ar:generalizes - 하위 주제를 포괄하는 상위 개념</option>
                            <option value="simplifies" ${existingType === 'simplifies' ? 'selected' : ''}>ar:simplifies - 복잡한 개념의 단순화 버전</option>
                            <option value="appliesTo" ${existingType === 'appliesTo' ? 'selected' : ''}>ar:appliesTo - 개념이 적용되는 실전 주제</option>
                            <option value="coOccursWith" ${existingType === 'coOccursWith' ? 'selected' : ''}>ar:coOccursWith - 함께 등장하는 병렬 주제</option>
                            <option value="conflictsWith" ${existingType === 'conflictsWith' ? 'selected' : ''}>ar:conflictsWith - 혼동 위험이 높은 개념 관계</option>
                            <option value="revives" ${existingType === 'revives' ? 'selected' : ''}>ar:revives - 잊힌 개념의 재활성화</option>
                            <option value="adjustsFor" ${existingType === 'adjustsFor' ? 'selected' : ''}>ar:adjustsFor - 학생 상황 기반 조정 관계</option>
                            <option value="bridgesTo" ${existingType === 'bridgesTo' ? 'selected' : ''}>ar:bridgesTo - 다른 학년·단원으로의 연결 다리</option>
                            <option value="confirms" ${existingType === 'confirms' ? 'selected' : ''}>ar:confirms - 전 단원의 결과를 검증하는 주제</option>
                            <option value="derivedFrom" ${existingType === 'derivedFrom' ? 'selected' : ''}>ar:derivedFrom - 수식적/논리적으로 파생된 관계</option>
                        </select>
                    </div>
                    ${existingLink ? '<div class="existing-relation-notice">⚠️ 기존 관계가 있습니다. 수정하거나 삭제할 수 있습니다.</div>' : ''}
                </div>
                <div class="popup-footer">
                    <button class="btn-secondary" onclick="closePopup();">취소</button>
                    ${existingLink ? '<button class="btn-danger" onclick="deleteRelationship(\'' + node1.id + '\', \'' + node2.id + '\');">삭제</button>' : ''}
                    <button class="btn-primary" onclick="saveRelationship(\'' + node1.id + '\', \'' + node2.id + '\');">저장</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(popup);
        
        // 팝업 외부 클릭 시 닫기
        popup.addEventListener('click', function(e) {
            if (e.target === popup) {
                popup.remove();
                selectedNodes = [];
                updateNodeSelection();
            }
        });
        
        // ESC 키로 닫기
        const escHandler = function(e) {
            if (e.key === 'Escape') {
                popup.remove();
                selectedNodes = [];
                updateNodeSelection();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
        
        // 전역 함수로 노출 (팝업 내부에서 호출)
        const closePopup = function() {
            popup.remove();
            selectedNodes = [];
            updateNodeSelection();
            document.removeEventListener('keydown', escHandler);
        };
        
        window.selectedNodes = selectedNodes;
        window.updateNodeSelection = updateNodeSelection;
        window.closePopup = closePopup;
        window.saveRelationship = function(node1Id, node2Id) {
            const direction = popup.querySelector('input[name="direction"]:checked').value;
            const type = popup.querySelector('#relationship-type-select').value;
            
            const sourceNode = direction === 'forward' ? node1 : node2;
            const targetNode = direction === 'forward' ? node2 : node1;
            
            // 기존 링크 제거
            if (existingLink) {
                const index = links.indexOf(existingLink);
                if (index >= 0) {
                    links.splice(index, 1);
                }
            }
            
            // 새 링크 추가
            const sourceIndex = nodes.findIndex(n => n.id === sourceNode.id);
            const targetIndex = nodes.findIndex(n => n.id === targetNode.id);
            
            if (sourceIndex >= 0 && targetIndex >= 0) {
                const newLink = {
                    source: sourceIndex,
                    target: targetIndex,
                    type: type,
                    value: 1
                };
                links.push(newLink);
                
                // 그래프 재그리기 (기존 요소 제거 후 다시 그리기)
                g.selectAll('.links').remove();
                g.selectAll('.nodes').remove();
                g.selectAll('.labels').remove();
                drawGraph();
                createSimulation();
                
                // 범례 업데이트
                updateLegend();
                
                console.log('관계 저장됨:', {
                    source: sourceNode.label,
                    target: targetNode.label,
                    type: type
                });
            }
            
            // 팝업 닫기 및 선택 해제
            closePopup();
        };
        
        window.deleteRelationship = function(node1Id, node2Id) {
            if (existingLink) {
                const index = links.indexOf(existingLink);
                if (index >= 0) {
                    links.splice(index, 1);
                    
                    // 그래프 재그리기 (기존 요소 제거 후 다시 그리기)
                    g.selectAll('.links').remove();
                    g.selectAll('.nodes').remove();
                    g.selectAll('.labels').remove();
                    drawGraph();
                    createSimulation();
                    
                    // 범례 업데이트
                    updateLegend();
                    
                    console.log('관계 삭제됨');
                }
            }
            
            // 팝업 닫기 및 선택 해제
            closePopup();
        };
    }

    // 이벤트 리스너 설정
    function setupEventListeners() {
        // 파일 선택 변경
        document.getElementById('owl-file-select').addEventListener('change', function() {
            const file = this.value;
            if (file) {
                window.location.href = '?file=' + encodeURIComponent(file);
            }
        });

        // 리셋 줌
        document.getElementById('reset-zoom').addEventListener('click', function() {
            svg.transition()
                .duration(750)
                .call(d3.zoom().transform, d3.zoomIdentity);
            g.attr('transform', '');
        });

        // 라벨 토글
        document.getElementById('toggle-labels').addEventListener('click', function() {
            showLabels = !showLabels;
            labelElements.style('opacity', showLabels ? 1 : 0);
        });

        // 레이아웃 변경
        document.getElementById('layout-select').addEventListener('change', function() {
            currentLayout = this.value;
            applyLayout(currentLayout);
        });
    }

    // 레이아웃 적용
    function applyLayout(layout) {
        simulation.stop();

        if (layout === 'hierarchical') {
            // 계층형 레이아웃 (간단한 구현)
            const stages = [...new Set(nodes.map(d => d.stage))].sort((a, b) => a - b);
            const stageHeight = height / (stages.length + 1);
            
            nodes.forEach(node => {
                const stageIndex = stages.indexOf(node.stage);
                const nodesInStage = nodes.filter(n => n.stage === node.stage);
                const nodeIndex = nodesInStage.indexOf(node);
                const nodesCount = nodesInStage.length;
                
                node.x = (width / (nodesCount + 1)) * (nodeIndex + 1);
                node.y = stageHeight * (stageIndex + 1);
                node.fx = node.x;
                node.fy = node.y;
            });
        } else if (layout === 'circular') {
            // 원형 레이아웃
            const radius = Math.min(width, height) / 3;
            const angleStep = (2 * Math.PI) / nodes.length;
            
            nodes.forEach((node, i) => {
                const angle = i * angleStep;
                node.x = width / 2 + radius * Math.cos(angle);
                node.y = height / 2 + radius * Math.sin(angle);
                node.fx = node.x;
                node.fy = node.y;
            });
        } else {
            // Force 레이아웃 (기본)
            nodes.forEach(node => {
                node.fx = null;
                node.fy = null;
            });
            createSimulation();
            return;
        }

        // 위치 업데이트
        ticked();
        
        // 애니메이션으로 이동
        nodeElements.transition()
            .duration(1000)
            .attr('cx', d => d.x)
            .attr('cy', d => d.y);
        
        labelElements.transition()
            .duration(1000)
            .attr('x', d => d.x)
            .attr('y', d => d.y);
    }

    // 초기화 실행 - graphData가 준비될 때까지 대기
    function waitForGraphData() {
        if (typeof window.graphData !== 'undefined') {
            console.log('graphData 준비됨');
            init();
        } else {
            console.log('graphData 대기 중...');
            setTimeout(waitForGraphData, 100);
        }
    }
    
    // DOM 로드 확인
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM 로드 완료');
            waitForGraphData();
        });
    } else {
        console.log('DOM 이미 로드됨');
        waitForGraphData();
    }
    
    // D3.js 로드 확인
    if (typeof d3 === 'undefined') {
        console.error('D3.js가 로드되지 않았습니다!');
    } else {
        console.log('D3.js 버전:', d3.version);
    }
})();

