// WebVTT Parser for audio-text synchronization
class WebVTTParser {
    constructor() {
        this.cues = [];
    }

    parse(webvttText) {
        if (!webvttText) return [];
        
        const lines = webvttText.split('\n');
        this.cues = [];
        let currentCue = null;
        
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();
            
            // Skip WEBVTT header and empty lines
            if (line === 'WEBVTT' || line === '') continue;
            
            // Check if this is a timing line
            const timingMatch = line.match(/^(\d{2}:\d{2}.\d{3})\s*-->\s*(\d{2}:\d{2}.\d{3})$/);
            if (timingMatch) {
                currentCue = {
                    startTime: this.parseTime(timingMatch[1]),
                    endTime: this.parseTime(timingMatch[2]),
                    text: ''
                };
                continue;
            }
            
            // If we have a current cue and this is text, add it
            if (currentCue && line) {
                if (currentCue.text) {
                    currentCue.text += '\n';
                }
                currentCue.text += line;
                
                // Check if next line is empty or timing, then save cue
                if (i + 1 >= lines.length || 
                    lines[i + 1].trim() === '' || 
                    lines[i + 1].match(/^\d{2}:\d{2}.\d{3}\s*-->\s*\d{2}:\d{2}.\d{3}$/)) {
                    this.cues.push(currentCue);
                    currentCue = null;
                }
            }
        }
        
        return this.cues;
    }
    
    parseTime(timeString) {
        const parts = timeString.split(':');
        const minutes = parseInt(parts[0]);
        const seconds = parseFloat(parts[1]);
        return minutes * 60 + seconds;
    }
    
    getCueAtTime(currentTime) {
        for (const cue of this.cues) {
            if (currentTime >= cue.startTime && currentTime <= cue.endTime) {
                return cue;
            }
        }
        return null;
    }
    
    getNextCueTime(currentTime) {
        for (const cue of this.cues) {
            if (cue.startTime > currentTime) {
                return cue.startTime;
            }
        }
        return null;
    }
}

// Audio-Text Synchronizer
class AudioTextSync {
    constructor(audioElement, textContainer, webvttData) {
        this.audio = audioElement;
        this.textContainer = textContainer;
        this.parser = new WebVTTParser();
        this.cues = this.parser.parse(webvttData);
        this.currentCueIndex = -1;
        this.textElements = [];
        
        this.initializeSync();
    }
    
    initializeSync() {
        // 오디오 시간 업데이트 이벤트 리스너
        this.audio.addEventListener('timeupdate', () => this.onTimeUpdate());
        
        // 텍스트 요소들 초기화
        this.initializeTextElements();
    }
    
    initializeTextElements() {
        // 텍스트 컨테이너의 모든 텍스트 라인을 가져옴
        const lines = this.textContainer.querySelectorAll('.explanation-line');
        this.textElements = Array.from(lines);
        
        // 각 큐와 텍스트 요소를 매칭
        this.cues.forEach((cue, index) => {
            // 큐의 텍스트와 매칭되는 요소 찾기
            const matchingElement = this.findMatchingElement(cue.text);
            if (matchingElement) {
                cue.element = matchingElement;
            }
        });
    }
    
    findMatchingElement(cueText) {
        // 텍스트 정규화 (공백, 특수문자 제거)
        const normalizedCueText = this.normalizeText(cueText);
        
        for (const element of this.textElements) {
            const elementText = this.normalizeText(element.textContent);
            if (elementText.includes(normalizedCueText) || normalizedCueText.includes(elementText)) {
                return element;
            }
        }
        return null;
    }
    
    normalizeText(text) {
        return text.replace(/[\s\n\r]+/g, ' ').trim();
    }
    
    onTimeUpdate() {
        const currentTime = this.audio.currentTime;
        const currentCue = this.parser.getCueAtTime(currentTime);
        
        if (currentCue) {
            const cueIndex = this.cues.indexOf(currentCue);
            if (cueIndex !== this.currentCueIndex) {
                this.highlightCue(cueIndex);
                this.currentCueIndex = cueIndex;
            }
        } else {
            this.clearHighlights();
            this.currentCueIndex = -1;
        }
    }
    
    highlightCue(cueIndex) {
        // 모든 하이라이트 제거
        this.clearHighlights();
        
        const cue = this.cues[cueIndex];
        if (cue.element) {
            // 현재 큐 하이라이트
            cue.element.classList.add('active-cue');
            
            // 스크롤 위치 조정 (부드럽게)
            cue.element.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
        
        // 이전에 재생된 큐들 표시
        for (let i = 0; i < cueIndex; i++) {
            if (this.cues[i].element) {
                this.cues[i].element.classList.add('played-cue');
            }
        }
    }
    
    clearHighlights() {
        this.textElements.forEach(element => {
            element.classList.remove('active-cue', 'played-cue');
        });
    }
    
    // 특정 텍스트로 이동
    seekToText(element) {
        const cue = this.cues.find(c => c.element === element);
        if (cue) {
            this.audio.currentTime = cue.startTime;
        }
    }
}

// Export for use in other files
window.WebVTTParser = WebVTTParser;
window.AudioTextSync = AudioTextSync;