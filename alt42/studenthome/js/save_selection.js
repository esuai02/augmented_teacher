// 선택 정보 저장 함수
function saveUserSelection(pageType, lastUnit, lastTopic, selectionData = {}) {
    const data = {
        userid: typeof studentId !== 'undefined' ? studentId : '',
        page_type: pageType,
        last_path: selectionData.path || '',
        last_unit: lastUnit,
        last_topic: lastTopic,
        selection_data: selectionData
    };
    
    fetch('save_selection.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            console.log('선택 저장 완료:', result);
        } else {
            console.error('선택 저장 실패:', result);
        }
    })
    .catch(error => {
        console.error('선택 저장 실패:', error);
    });
}

// 마지막 선택 하이라이트
function highlightLastSelection(selector, lastUnit) {
    const elements = document.querySelectorAll(selector);
    elements.forEach((el) => {
        if (el.textContent.includes(lastUnit)) {
            el.classList.add('last-selected');
            el.style.border = '2px solid #3b82f6';
            el.style.boxShadow = '0 0 10px rgba(59, 130, 246, 0.3)';
            el.style.position = 'relative';
            
            // 라벨 추가
            const label = document.createElement('div');
            label.style.position = 'absolute';
            label.style.top = '-10px';
            label.style.right = '-10px';
            label.style.background = '#3b82f6';
            label.style.color = 'white';
            label.style.padding = '2px 8px';
            label.style.borderRadius = '12px';
            label.style.fontSize = '0.75rem';
            label.style.fontWeight = 'bold';
            label.textContent = '최근';
            el.appendChild(label);
        }
    });
}