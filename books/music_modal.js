// 음악 휴식 모달 시스템 JavaScript

// 모달 표시 완료 플래그
let modalCheckCompleted = false;

document.addEventListener('DOMContentLoaded', function() {
  console.log('[music_modal.js] 모달 시스템 시작');

  // 즉시 한 번 체크 (이미 로드된 음악이 있을 수 있음)
  setTimeout(checkAndShowMusicModal, 500);

  // 1. MutationObserver로 musicAudioSource 감시
  const musicSource = document.getElementById('musicAudioSource');
  if (musicSource) {
    console.log('[music_modal.js] musicAudioSource 요소 발견, Observer 시작');
    const observer = new MutationObserver(function(mutations) {
      mutations.forEach(function(mutation) {
        if (mutation.type === 'attributes' && mutation.attributeName === 'src') {
          console.log('[music_modal.js] MutationObserver: src 속성 변경 감지, 새 값:', musicSource.src);
          if (!modalCheckCompleted) {
            checkAndShowMusicModal();
          }
        }
      });
    });
    observer.observe(musicSource, { attributes: true });
  } else {
    console.warn('[music_modal.js] musicAudioSource 요소 없음');
  }

  // 2. window.currentMusicUrl 주기적 폴링 (더 긴 시간, 더 자주)
  let lastMusicUrl = '';
  let checkCount = 0;
  const maxChecks = 100; // 20초 = 100 * 200ms

  const checkInterval = setInterval(function() {
    checkCount++;

    // currentMusicUrl 체크
    if (window.currentMusicUrl && window.currentMusicUrl !== lastMusicUrl && window.currentMusicUrl.trim() !== '') {
      console.log('[music_modal.js] Polling: currentMusicUrl 발견:', window.currentMusicUrl);
      lastMusicUrl = window.currentMusicUrl;
      if (!modalCheckCompleted) {
        checkAndShowMusicModal();
      }
    }

    // musicAudioSource src 체크 (폴링 백업)
    if (musicSource && musicSource.src && musicSource.src !== window.location.href) {
      const currentSrc = musicSource.src;
      if (currentSrc !== lastMusicUrl && currentSrc.trim() !== '') {
        console.log('[music_modal.js] Polling: musicAudioSource.src 발견:', currentSrc);
        lastMusicUrl = currentSrc;
        if (!modalCheckCompleted) {
          checkAndShowMusicModal();
        }
      }
    }

    // 최대 체크 횟수 도달시 종료
    if (checkCount >= maxChecks) {
      clearInterval(checkInterval);
      console.log('[music_modal.js] Polling 종료 (20초 경과)');
    }
  }, 200);

  // 3. audio 요소의 loadedmetadata 이벤트 감지
  const audioPlayer = document.getElementById('musicAudioPlayer');
  if (audioPlayer) {
    audioPlayer.addEventListener('loadedmetadata', function() {
      console.log('[music_modal.js] Audio loadedmetadata 이벤트 발생');
      if (!modalCheckCompleted) {
        checkAndShowMusicModal();
      }
    });
  }
});

function checkAndShowMusicModal() {
  // 이미 체크 완료했으면 스킵
  if (modalCheckCompleted) {
    return;
  }

  const isTargetPage = checkPageNumber();
  const hasAudio = checkAudioExists();

  console.log('[music_modal.js] checkAndShowMusicModal() 조건 체크:', {
    isTargetPage: isTargetPage,
    hasAudio: hasAudio
  });

  // 두 가지 조건이 모두 충족되면 모달 표시
  if (isTargetPage && hasAudio) {
    showMusicRelaxModal();
    modalCheckCompleted = true;
    console.log('[music_modal.js] 모달 표시 완료');
  }
}

function checkForMasterPattern() {
  const menuItems = document.querySelectorAll('#contentslist td, #contentslist2 td, #contentslist3 td');
  console.log('[music_modal.js] checkForMasterPattern() 요소 개수:', menuItems.length);

  for (let item of menuItems) {
    if (item.textContent.includes('대표유형')) {
      console.log('[music_modal.js] ✓ 대표유형 발견');
      return true;
    }
  }
  console.log('[music_modal.js] ✗ 대표유형 없음');
  return false;
}

function checkPageNumber() {
  const urlParams = new URLSearchParams(window.location.search);
  const pageNum = parseInt(urlParams.get('page')) || 1;
  const isTarget = (pageNum % 4 === 1);
  console.log('[music_modal.js] checkPageNumber() 페이지:', pageNum, ', 조건(page%4===1):', isTarget);
  return isTarget;
}

function checkAudioExists() {
  // 1. window.currentMusicUrl 변수 확인
  if (window.currentMusicUrl && window.currentMusicUrl.trim() !== '') {
    console.log('[music_modal.js] ✓ checkAudioExists() currentMusicUrl 존재:', window.currentMusicUrl);
    return true;
  }

  // 2. musicAudioSource의 src 속성 확인
  const musicSource = document.getElementById('musicAudioSource');
  if (!musicSource) {
    console.log('[music_modal.js] ✗ checkAudioExists() musicAudioSource 요소 없음');
    return false;
  }

  const audioSrc = musicSource.src || musicSource.getAttribute('src');
  const hasAudio = audioSrc && audioSrc.trim() !== '' && audioSrc !== window.location.href;

  console.log('[music_modal.js] checkAudioExists() musicAudioSource.src:', audioSrc, ', 결과:', hasAudio);
  return hasAudio;
}

function showMusicRelaxModal() {
  if (document.getElementById('musicRelaxModal')) {
    console.log('[music_modal.js] showMusicRelaxModal() 모달이 이미 존재함, 스킵');
    return;
  }

  const modalHTML = `
    <div id="musicRelaxModal" class="music-relax-modal">
      <div class="music-relax-modal-content">
        <span class="music-relax-close" onclick="closeMusicRelaxModal()">&times;</span>
        <div class="music-relax-icon">🎵</div>
        <h2>음악으로 지친 뇌에게 휴식을 선물해 주세요</h2>
        <button class="music-relax-play-btn" onclick="playMusicAndCloseModal()">
          <span class="play-icon">▶️</span>
          <span class="play-text">재생하기</span>
        </button>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML('beforeend', modalHTML);
  const modal = document.getElementById('musicRelaxModal');
  modal.style.display = 'block';
  console.log('[music_modal.js] ✓ showMusicRelaxModal() 모달 DOM 추가 및 표시 완료');

  modal.addEventListener('click', function(event) {
    if (event.target === modal) {
      closeMusicRelaxModal();
    }
  });
}

function playMusicAndCloseModal() {
  console.log('[music_modal.js] playMusicAndCloseModal() 호출됨');
  const audioPlayer = document.getElementById('musicAudioPlayer');

  if (audioPlayer) {
    audioPlayer.play().then(() => {
      console.log('[music_modal.js] ✓ 음악 재생 시작');
    }).catch(error => {
      console.error('[music_modal.js] ✗ 음악 재생 실패:', error);
      alert('음악을 재생할 수 없습니다. 페이지를 새로고침해주세요.');
    });
  } else {
    console.warn('[music_modal.js] ✗ musicAudioPlayer 요소 없음');
    alert('재생할 음악이 없습니다.');
  }

  closeMusicRelaxModal();
}

function closeMusicRelaxModal() {
  const modal = document.getElementById('musicRelaxModal');
  if (modal) {
    modal.style.animation = 'fadeOut 0.3s ease';
    setTimeout(() => {
      modal.style.display = 'none';
      modal.remove();
      console.log('[music_modal.js] 모달 닫힘 (fadeOut 완료)');
    }, 300);
  }
}

document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    const modal = document.getElementById('musicRelaxModal');
    if (modal && modal.style.display === 'block') {
      console.log('[music_modal.js] ESC 키로 모달 닫기');
      closeMusicRelaxModal();
    }
  }
});
