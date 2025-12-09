// 전역 변수
let currentFile = '';
let fileList = [];
let relationsMap = {};

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function () {
    loadFileList();
    setupSearch();
});

// 파일 목록 로드
async function loadFileList() {
    try {
        const response = await fetch(API_URL + '?action=list');
        const data = await response.json();

        if (data.success) {
            fileList = data.data.files;
            renderFileTree(fileList);
            buildRelationsMap(fileList);
        } else {
            showError('파일 목록을 불러올 수 없습니다: ' + data.error);
        }
    } catch (error) {
        showError('오류 발생: ' + error.message);
    }
}

// 폴더 우선순위 맵 (낮은 숫자 = 높은 우선순위)
const folderPriority = {
    'ontology_engineering': 1,
    'agents/docs': 2,
    'ontology_engineering/docs': 3,
    'ontology_engineering/DesigningOfOntology': 4,
    'agent01_onboarding/ontology': 5,
    'agent04_inspect_weakpoints/ontology': 6,
    'agent04_inspect_weakpoints/tasks': 7,
    'agent22_module_improvement/tasks': 8
};

// 파일 우선순위 계산 (파일명 기반)
function getFilePriority(filename) {
    const lower = filename.toLowerCase();

    // 이미 숫자로 시작하는 파일명 (00_, 01_, 02_ 등)
    const numberMatch = filename.match(/^(\d+)[._-]/);
    if (numberMatch) {
        return parseInt(numberMatch[1]);
    }

    // 특정 파일명 우선순위
    if (lower.includes('contents')) return 1;
    if (lower.includes('readme')) return 2;
    if (lower.includes('architecture') || lower.includes('v3')) return 3;
    if (lower.includes('firstprinciple')) return 4;
    if (lower.includes('guide')) return 10;
    if (lower.includes('checklist') || lower.includes('workflow')) return 20;
    if (lower.includes('report') || lower.includes('check') || lower.includes('status')) return 30;
    if (lower.includes('implementation') || lower.includes('summary')) return 35;
    if (lower.includes('issue')) return 36;
    if (lower.includes('principles') || lower.includes('priciples')) return 40;
    if (lower.includes('triples')) return 50;
    if (lower.includes('sparql') || lower.includes('inference') || lower.includes('rules')) return 60;
    if (lower.includes('validation') || lower.includes('use_case')) return 70;
    if (lower.endsWith('.py')) return 80;
    if (lower.includes('protege')) return 90;
    if (lower.includes('cleanup') || lower.includes('plan')) return 95;
    if (lower.includes('prd') || lower.includes('task')) return 100;

    // 기본값
    return 999;
}

// 파일 표시명 생성 (순차 인덱스 기반 - 중복 방지)
function getFileDisplayName(filename, index) {
    // 이미 숫자로 시작하면 그대로 사용
    if (filename.match(/^\d+[._-]/)) {
        return filename;
    }

    // index가 제공되면 순차 번호 추가
    if (index !== undefined) {
        const displayNumber = String(index + 1).padStart(2, '0');
        return `${displayNumber}. ${filename}`;
    }

    return filename;
}

// 파일 정렬 함수
function sortFiles(files) {
    return files.sort((a, b) => {
        const priorityA = getFilePriority(a.name);
        const priorityB = getFilePriority(b.name);

        if (priorityA !== priorityB) {
            return priorityA - priorityB;
        }

        // 우선순위가 같으면 알파벳순
        return a.name.localeCompare(b.name);
    });
}

// 폴더 표시명 생성 (번호 추가)
function getFolderDisplayName(folder) {
    const priority = folderPriority[folder];
    if (priority) {
        const paddedNumber = String(priority).padStart(2, '0');
        return `${paddedNumber}. ${folder}`;
    }
    return folder;
}

// 폴더 정렬 함수
function sortFolders(folders) {
    return folders.sort((a, b) => {
        const priorityA = folderPriority[a] || 999;
        const priorityB = folderPriority[b] || 999;

        if (priorityA !== priorityB) {
            return priorityA - priorityB;
        }

        // 우선순위가 같으면 알파벳순
        return a.localeCompare(b);
    });
}

// 파일 트리 렌더링 (폴더 접기 기능 포함)
function renderFileTree(files, filter = '') {
    const tree = document.getElementById('file-tree');
    tree.innerHTML = '';

    const filtered = files.filter(file =>
        file.name.toLowerCase().includes(filter.toLowerCase()) ||
        file.path.toLowerCase().includes(filter.toLowerCase())
    );

    // 폴더별 그룹화
    const folders = {};
    filtered.forEach(file => {
        const parts = file.path.split('/');
        const folder = parts.slice(0, -1).join('/') || 'root';
        if (!folders[folder]) {
            folders[folder] = [];
        }
        folders[folder].push(file);
    });

    // 폴더를 우선순위대로 정렬
    const sortedFolderKeys = sortFolders(Object.keys(folders));

    // 트리 렌더링
    sortedFolderKeys.forEach(folder => {
        if (folder !== 'root') {
            // 폴더 컨테이너 생성
            const folderContainer = document.createElement('li');
            folderContainer.className = 'folder-container';

            // 폴더 헤더 생성
            const folderHeader = document.createElement('div');
            folderHeader.className = 'folder-header';
            folderHeader.setAttribute('data-folder', folder);

            const folderIcon = document.createElement('span');
            folderIcon.className = 'folder-icon';
            folderIcon.textContent = '📁'; // 기본 접힌 상태

            const folderName = document.createElement('span');
            folderName.className = 'folder-name';
            folderName.textContent = getFolderDisplayName(folder);

            folderHeader.appendChild(folderIcon);
            folderHeader.appendChild(folderName);
            folderHeader.classList.add('collapsed'); // 기본 접힌 상태 클래스 추가

            // 폴더 클릭 이벤트
            folderHeader.onclick = function (e) {
                e.stopPropagation();
                toggleFolder(folder);
            };

            // 파일 목록 컨테이너 생성
            const fileList = document.createElement('ul');
            fileList.className = 'folder-files';
            fileList.setAttribute('data-folder', folder);
            fileList.style.display = 'none'; // 기본적으로 숨김 (접힌 상태)

            // 파일을 우선순위대로 정렬
            const sortedFiles = sortFiles(folders[folder]);

            sortedFiles.forEach((file, index) => {
                const li = document.createElement('li');
                const a = document.createElement('a');
                a.href = '#';
                a.textContent = getFileDisplayName(file.name, index);
                a.onclick = (e) => {
                    e.preventDefault();
                    loadFile(file.path);
                    // 활성화 표시
                    document.querySelectorAll('.file-tree a').forEach(link => {
                        link.classList.remove('active');
                    });
                    a.classList.add('active');
                };
                li.appendChild(a);
                fileList.appendChild(li);
            });

            folderContainer.appendChild(folderHeader);
            folderContainer.appendChild(fileList);
            tree.appendChild(folderContainer);
        } else {
            // root 폴더의 파일들
            const sortedFiles = sortFiles(folders[folder]);

            sortedFiles.forEach((file, index) => {
                const li = document.createElement('li');
                const a = document.createElement('a');
                a.href = '#';
                a.textContent = getFileDisplayName(file.name, index);
                a.onclick = (e) => {
                    e.preventDefault();
                    loadFile(file.path);
                    // 활성화 표시
                    document.querySelectorAll('.file-tree a').forEach(link => {
                        link.classList.remove('active');
                    });
                    a.classList.add('active');
                };
                li.appendChild(a);
                tree.appendChild(li);
            });
        }
    });
}

// 폴더 접기/펼치기 토글 (Accordion 방식)
function toggleFolder(folder) {
    const folderHeader = document.querySelector(`.folder-header[data-folder="${folder}"]`);
    const fileList = document.querySelector(`.folder-files[data-folder="${folder}"]`);
    const folderIcon = folderHeader.querySelector('.folder-icon');

    const isCurrentlyCollapsed = fileList.style.display === 'none';

    if (isCurrentlyCollapsed) {
        // 모든 폴더 닫기
        document.querySelectorAll('.folder-files').forEach(fl => {
            fl.style.display = 'none';
        });
        document.querySelectorAll('.folder-icon').forEach(icon => {
            icon.textContent = '📁';
        });
        document.querySelectorAll('.folder-header').forEach(header => {
            header.classList.add('collapsed');
        });

        // 선택한 폴더만 열기
        fileList.style.display = 'block';
        folderIcon.textContent = '📂';
        folderHeader.classList.remove('collapsed');
    } else {
        // 이미 열린 폴더를 다시 클릭하면 닫기
        fileList.style.display = 'none';
        folderIcon.textContent = '📁';
        folderHeader.classList.add('collapsed');
    }
}

// 파일 로드
async function loadFile(filepath) {
    try {
        currentFile = filepath;
        document.getElementById('file-title').textContent = filepath.split('/').pop();

        const response = await fetch(API_URL + '?action=read&file=' + encodeURIComponent(filepath));
        const data = await response.json();

        if (data.success) {
            document.getElementById('markdown-editor').value = data.data.content;
            updatePreview(data.data.content);
            showRelations(filepath);
            // 기본적으로 미리보기 모드로 전환
            switchView('preview');
        } else {
            showError('파일을 불러올 수 없습니다: ' + data.error);
        }
    } catch (error) {
        showError('오류 발생: ' + error.message);
    }
}

// 뷰 전환 (편집/미리보기)
function switchView(mode) {
    const editor = document.getElementById('markdown-editor');
    const preview = document.getElementById('markdown-preview');
    const editTab = document.getElementById('edit-tab');
    const previewTab = document.getElementById('preview-tab');

    if (mode === 'edit') {
        editor.style.display = 'block';
        preview.style.display = 'none';
        editTab.classList.add('active');
        previewTab.classList.remove('active');
    } else {
        editor.style.display = 'none';
        preview.style.display = 'block';
        previewTab.classList.add('active');
        editTab.classList.remove('active');
        // 에디터 내용을 미리보기로 업데이트
        updatePreview(editor.value);
    }
}

// 마크다운을 HTML로 변환
function markdownToHtml(markdown) {
    if (!markdown) return '';

    let html = markdown;

    // 코드 블록 먼저 처리 (다른 변환에 영향받지 않도록)
    const codeBlocks = [];
    let codeBlockIndex = 0;
    html = html.replace(/```(\w+)?\n([\s\S]*?)```/g, function (match, lang, code) {
        const placeholder = `___CODE_BLOCK_${codeBlockIndex}___`;
        const escapedCode = escapeHtml(code);
        codeBlocks[codeBlockIndex] = `<pre><code class="language-${lang || ''}">${escapedCode}</code></pre>`;
        codeBlockIndex++;
        return placeholder;
    });

    // 인라인 코드 처리
    html = html.replace(/`([^`\n]+)`/g, '<code>$1</code>');

    // 헤더 처리 (순서 중요: #### -> ### -> ## -> #)
    html = html.replace(/^#### (.+)$/gm, '<h4>$1</h4>');
    html = html.replace(/^### (.+)$/gm, '<h3>$1</h3>');
    html = html.replace(/^## (.+)$/gm, '<h2>$1</h2>');
    html = html.replace(/^# (.+)$/gm, '<h1>$1</h1>');

    // 강조 (Bold) - **text** 또는 __text__
    html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/__(.+?)__/g, '<strong>$1</strong>');

    // 이탤릭 - *text* 또는 _text_
    html = html.replace(/\*([^*\n]+?)\*/g, '<em>$1</em>');
    html = html.replace(/_([^_\n]+?)_/g, '<em>$1</em>');

    // 링크 - [text](url)
    html = html.replace(/\[([^\]]+)\]\(([^\)]+)\)/g, '<a href="$2" onclick="handleMarkdownLink(event, \'$2\'); return false;">$1</a>');

    // 수평선
    html = html.replace(/^---$/gm, '<hr>');
    html = html.replace(/^\*\*\*$/gm, '<hr>');

    // 인용구 (blockquote)
    html = html.replace(/^> (.+)$/gm, '<blockquote>$1</blockquote>');
    // 연속된 blockquote를 하나로 합치기
    html = html.replace(/(<\/blockquote>\s*<blockquote>)+/g, '<br>');

    // 테이블 및 목록 처리 (줄 단위로 처리)
    const htmlLines = html.split('\n');
    const finalLines = [];
    let inTable = false;
    let tableHtml = '';
    let inOrderedList = false;
    let inUnorderedList = false;
    let listItems = [];
    let prevLineWasTableSeparator = false;

    for (let i = 0; i < htmlLines.length; i++) {
        const line = htmlLines[i];
        const trimmedLine = line.trim();

        // 테이블 처리
        if (trimmedLine.startsWith('|') && trimmedLine.endsWith('|')) {
            // 테이블 구분선 체크
            if (trimmedLine.match(/^\|[\s\-:]+\|$/)) {
                prevLineWasTableSeparator = true;
                continue; // 구분선은 건너뛰기
            }

            if (!inTable) {
                inTable = true;
                tableHtml = '<table><thead><tr>';
            }

            const cells = trimmedLine.split('|').map(cell => cell.trim()).filter(cell => cell);

            if (prevLineWasTableSeparator) {
                // 헤더 행 (이전 줄이 구분선이었음)
                cells.forEach(cell => {
                    tableHtml += `<th>${cell}</th>`;
                });
                tableHtml += '</tr></thead><tbody>';
                prevLineWasTableSeparator = false;
            } else {
                // 데이터 행
                if (tableHtml.includes('</thead>')) {
                    // 이미 헤더가 있으면 tbody에 추가
                    tableHtml += '<tr>';
                } else {
                    // 헤더가 없으면 첫 행을 헤더로
                    tableHtml = '<table><thead><tr>';
                    cells.forEach(cell => {
                        tableHtml += `<th>${cell}</th>`;
                    });
                    tableHtml += '</tr></thead><tbody><tr>';
                }
                cells.forEach(cell => {
                    tableHtml += `<td>${cell}</td>`;
                });
                tableHtml += '</tr>';
            }
            continue;
        } else {
            // 테이블 종료
            if (inTable) {
                tableHtml += '</tbody></table>';
                finalLines.push(tableHtml);
                tableHtml = '';
                inTable = false;
                prevLineWasTableSeparator = false;
            }
        }

        // 목록 처리
        const orderedMatch = trimmedLine.match(/^(\d+)\. (.+)$/);
        const unorderedMatch = trimmedLine.match(/^[\-\*] (.+)$/);

        if (orderedMatch) {
            if (!inOrderedList && listItems.length > 0) {
                // 이전 목록 종료
                if (inUnorderedList) {
                    finalLines.push('<ul>' + listItems.join('') + '</ul>');
                    inUnorderedList = false;
                }
                listItems = [];
            }
            inOrderedList = true;
            listItems.push('<li>' + orderedMatch[2] + '</li>');
        } else if (unorderedMatch) {
            if (!inUnorderedList && listItems.length > 0) {
                // 이전 목록 종료
                if (inOrderedList) {
                    finalLines.push('<ol>' + listItems.join('') + '</ol>');
                    inOrderedList = false;
                }
                listItems = [];
            }
            inUnorderedList = true;
            listItems.push('<li>' + unorderedMatch[1] + '</li>');
        } else {
            // 목록 종료
            if (listItems.length > 0) {
                if (inOrderedList) {
                    finalLines.push('<ol>' + listItems.join('') + '</ol>');
                    inOrderedList = false;
                } else if (inUnorderedList) {
                    finalLines.push('<ul>' + listItems.join('') + '</ul>');
                    inUnorderedList = false;
                }
                listItems = [];
            }
            finalLines.push(line);
        }
    }

    // 마지막 테이블 처리
    if (inTable) {
        tableHtml += '</tbody></table>';
        finalLines.push(tableHtml);
    }

    // 마지막 목록 처리
    if (listItems.length > 0) {
        if (inOrderedList) {
            finalLines.push('<ol>' + listItems.join('') + '</ol>');
        } else if (inUnorderedList) {
            finalLines.push('<ul>' + listItems.join('') + '</ul>');
        }
    }

    html = finalLines.join('\n');

    // 코드 블록 복원
    codeBlocks.forEach((codeBlock, index) => {
        html = html.replace(`___CODE_BLOCK_${index}___`, codeBlock);
    });

    // 줄바꿈 처리 (블록 요소 제외)
    const blockElements = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'table', 'pre', 'blockquote', 'hr'];
    html = html.split('\n').map(line => {
        const trimmed = line.trim();
        if (!trimmed) return '';

        // 블록 요소는 그대로
        if (blockElements.some(tag => trimmed.startsWith('<' + tag))) {
            return trimmed;
        }

        // 이미 HTML 태그가 있으면 그대로
        if (trimmed.startsWith('<')) {
            return trimmed;
        }

        // 일반 텍스트는 <p>로 감싸기
        return '<p>' + trimmed + '</p>';
    }).filter(line => line).join('\n');

    // 빈 단락 제거 및 정리
    html = html.replace(/<p><\/p>/g, '');
    html = html.replace(/<p>(<h[1-6]>)/g, '$1');
    html = html.replace(/(<\/h[1-6]>)<\/p>/g, '$1');
    html = html.replace(/<p>(<ul>|<ol>|<table>|<pre>|<blockquote>|<hr>)/g, '$1');
    html = html.replace(/(<\/ul>|<\/ol>|<\/table>|<\/pre>|<\/blockquote>|<\/hr>)<\/p>/g, '$1');
    html = html.replace(/(<\/ul>|<\/ol>|<\/table>|<\/pre>|<\/blockquote>|<\/hr>)\s*<p>/g, '$1');

    return html;
}

// HTML 이스케이프
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 미리보기 업데이트
function updatePreview(content) {
    const preview = document.getElementById('markdown-preview');
    preview.innerHTML = markdownToHtml(content);
}

// 마크다운 링크 클릭 핸들러
function handleMarkdownLink(event, linkPath) {
    event.preventDefault();

    // 경로 정규화 헬퍼 함수 (중복 세그먼트 제거)
    function normalizePath(path) {
        const parts = path.split('/').filter(p => p && p !== '.');
        const normalized = [];

        for (let part of parts) {
            if (part === '..') {
                normalized.pop();
            } else {
                // 중복 확인: 같은 세그먼트가 반복되면 한 번만 추가
                if (normalized[normalized.length - 1] !== part) {
                    normalized.push(part);
                }
            }
        }

        return normalized.join('/');
    }

    let targetPath = linkPath;

    // 상대 경로를 절대 경로로 변환
    if (linkPath.startsWith('./') || linkPath.startsWith('../')) {
        const currentDir = currentFile.split('/').slice(0, -1).join('/');

        if (linkPath.startsWith('./')) {
            targetPath = currentDir + '/' + linkPath.substring(2);
        } else if (linkPath.startsWith('../')) {
            const parts = currentFile.split('/');
            const linkParts = linkPath.split('/');
            let depth = 0;
            linkParts.forEach(part => {
                if (part === '..') depth++;
            });
            targetPath = parts.slice(0, -(depth + 1)).join('/') + '/' +
                linkParts.slice(depth).join('/');
        }
    }

    // 경로 정규화 (중복 제거)
    targetPath = normalizePath(targetPath);

    loadFile(targetPath);
}

// 에디터 내용 변경 시 실시간 미리보기 업데이트 (미리보기 모드일 때만)
let previewUpdateTimeout;
document.addEventListener('DOMContentLoaded', function () {
    const editor = document.getElementById('markdown-editor');
    if (editor) {
        editor.addEventListener('input', function () {
            const preview = document.getElementById('markdown-preview');
            if (preview.style.display !== 'none') {
                clearTimeout(previewUpdateTimeout);
                previewUpdateTimeout = setTimeout(() => {
                    updatePreview(editor.value);
                }, 300); // 300ms 디바운스
            }
        });
    }
});

// 파일 저장
async function saveFile() {
    if (!currentFile) {
        showError('저장할 파일이 없습니다.');
        return;
    }

    // 에디터에서 현재 내용 가져오기
    const editor = document.getElementById('markdown-editor');
    const content = editor.value;

    if (!content && content !== '') {
        showError('저장할 내용이 없습니다.');
        return;
    }

    try {
        // 저장 중 표시
        const saveBtn = document.querySelector('.btn-primary');
        const originalText = saveBtn.textContent;
        saveBtn.textContent = '💾 저장 중...';
        saveBtn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'write');
        formData.append('file', currentFile);
        formData.append('content', content);

        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        // 버튼 상태 복원
        saveBtn.textContent = originalText;
        saveBtn.disabled = false;

        if (data.success) {
            showSuccess(`파일이 저장되었습니다. (${data.data.timestamp}) - ${data.data.bytes_written} bytes`);
            // 관계 맵 업데이트
            buildRelationsMap(fileList);
            showRelations(currentFile);
            // 미리보기 업데이트
            updatePreview(content);
        } else {
            showError('저장 실패: ' + data.error);
        }
    } catch (error) {
        // 버튼 상태 복원
        const saveBtn = document.querySelector('.btn-primary');
        saveBtn.textContent = '💾 저장';
        saveBtn.disabled = false;

        showError('오류 발생: ' + error.message);
        console.error('Save error:', error);
    }
}

// 파일 내용 복사 (폴더+파일명+내용)
async function copyFileContent() {
    if (!currentFile) {
        showError('복사할 파일이 없습니다.');
        return;
    }

    try {
        // 에디터에서 현재 내용 가져오기
        const editor = document.getElementById('markdown-editor');
        let content = editor.value;

        // 에디터가 숨겨져 있거나 비어있으면 파일에서 읽기
        if (!content || content.trim() === '') {
            const response = await fetch(API_URL + '?action=read&file=' + encodeURIComponent(currentFile));
            const data = await response.json();
            if (data.success) {
                content = data.data.content;
            } else {
                showError('파일 내용을 불러올 수 없습니다: ' + data.error);
                return;
            }
        }

        // 복사할 텍스트 구성: 폴더+파일명 + 빈 줄 + 내용
        const copyText = currentFile + '\n\n' + content;

        // 클립보드에 복사
        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(copyText);
            showSuccess('클립보드에 복사되었습니다. (' + currentFile.split('/').pop() + ')');
        } else {
            // 구형 브라우저 지원 (fallback)
            const textArea = document.createElement('textarea');
            textArea.value = copyText;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showSuccess('클립보드에 복사되었습니다. (' + currentFile.split('/').pop() + ')');
                } else {
                    showError('복사에 실패했습니다. 브라우저를 확인해주세요.');
                }
            } catch (err) {
                showError('복사 중 오류가 발생했습니다: ' + err.message);
            } finally {
                document.body.removeChild(textArea);
            }
        }
    } catch (error) {
        showError('복사 중 오류가 발생했습니다: ' + error.message);
        console.error('Copy error:', error);
    }
}

// 관계 맵 구축
async function buildRelationsMap(files) {
    relationsMap = {};

    for (const file of files) {
        try {
            const response = await fetch(API_URL + '?action=read&file=' + encodeURIComponent(file.path));
            const data = await response.json();

            if (data.success) {
                const links = extractLinks(data.data.content);
                relationsMap[file.path] = links;
            }
        } catch (error) {
            console.error('Error loading file for relations:', file.path, error);
        }
    }
}

// 마크다운에서 링크 추출
function extractLinks(content) {
    const links = [];
    // 마크다운 링크 패턴: [텍스트](경로)
    const linkRegex = /\[([^\]]+)\]\(([^\)]+)\)/g;
    let match;

    while ((match = linkRegex.exec(content)) !== null) {
        links.push({
            text: match[1],
            path: match[2]
        });
    }

    return links;
}

// 관계 표시 (인라인) - 처음 4개만 표시
function showRelations(filepath) {
    const graph = document.getElementById('relations-graph');
    graph.innerHTML = '';

    const relations = relationsMap[filepath] || [];

    if (relations.length === 0) {
        graph.innerHTML = '<span style="color: #999; font-size: 12px;">연결된 문서 없음</span>';
        return;
    }

    const maxInitialDisplay = 4;
    const hasMore = relations.length > maxInitialDisplay;

    // 관계 링크를 표시하는 함수
    const createRelationNode = (link, index) => {
        const node = document.createElement('span');
        node.className = 'relation-node';
        const a = document.createElement('a');
        a.href = '#';
        a.className = 'relation-link';
        a.textContent = link.text || link.path.split('/').pop();
        a.onclick = (e) => {
            e.preventDefault();
            // 상대 경로를 절대 경로로 변환
            let targetPath = link.path;
            if (link.path.startsWith('./')) {
                const currentDir = filepath.split('/').slice(0, -1).join('/');
                targetPath = currentDir + '/' + link.path.substring(2);
            } else if (link.path.startsWith('../')) {
                const parts = filepath.split('/');
                const linkParts = link.path.split('/');
                let depth = 0;
                linkParts.forEach(part => {
                    if (part === '..') depth++;
                });
                targetPath = parts.slice(0, -(depth + 1)).join('/') + '/' +
                    linkParts.slice(depth).join('/');
            }
            loadFile(targetPath);
        };
        node.appendChild(a);
        return node;
    };

    // 구분자 생성 함수
    const createSeparator = () => {
        const separator = document.createElement('span');
        separator.textContent = ' • ';
        separator.style.color = '#ccc';
        separator.style.margin = '0 4px';
        return separator;
    };

    // 처음 4개 표시
    relations.slice(0, maxInitialDisplay).forEach((link, index) => {
        graph.appendChild(createRelationNode(link, index));
        if (index < Math.min(maxInitialDisplay, relations.length) - 1) {
            graph.appendChild(createSeparator());
        }
    });

    // 5개 이상이면 더보기 버튼과 숨겨진 항목 추가
    if (hasMore) {
        // 숨겨진 관계 컨테이너
        const hiddenContainer = document.createElement('span');
        hiddenContainer.id = 'hidden-relations';
        hiddenContainer.style.display = 'none';

        relations.slice(maxInitialDisplay).forEach((link, index) => {
            hiddenContainer.appendChild(createSeparator());
            hiddenContainer.appendChild(createRelationNode(link, index + maxInitialDisplay));
        });

        graph.appendChild(hiddenContainer);

        // 더보기 버튼
        const moreButton = document.createElement('span');
        moreButton.innerHTML = ' <a href="#" id="show-more-relations" style="color: #007bff; font-size: 12px; margin-left: 8px;">더보기 (' + (relations.length - maxInitialDisplay) + ')</a>';
        moreButton.querySelector('a').onclick = (e) => {
            e.preventDefault();
            const hidden = document.getElementById('hidden-relations');
            const btn = document.getElementById('show-more-relations');
            if (hidden.style.display === 'none') {
                hidden.style.display = 'inline';
                btn.textContent = '접기';
            } else {
                hidden.style.display = 'none';
                btn.textContent = '더보기 (' + (relations.length - maxInitialDisplay) + ')';
            }
        };
        graph.appendChild(moreButton);
    }
}

// 검색 기능
function setupSearch() {
    const searchBox = document.getElementById('search-box');
    searchBox.addEventListener('input', (e) => {
        renderFileTree(fileList, e.target.value);
    });
}

// 상태 메시지 표시
function showSuccess(message) {
    const status = document.getElementById('status-message');
    status.className = 'status-message success';
    status.textContent = message;
    setTimeout(() => {
        status.className = 'status-message';
    }, 3000);
}

function showError(message) {
    const status = document.getElementById('status-message');
    status.className = 'status-message error';
    status.textContent = message;
    setTimeout(() => {
        status.className = 'status-message';
    }, 5000);
}

// 키보드 단축키
document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        saveFile();
    }
});

