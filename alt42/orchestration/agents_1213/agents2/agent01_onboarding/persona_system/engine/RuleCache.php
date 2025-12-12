<?php
/**
 * RuleCache - 규칙 캐싱 시스템
 *
 * YAML 규칙 파싱 결과를 메모리/파일 캐시하여 성능 최적화합니다.
 *
 * @package AugmentedTeacher\Agent01\PersonaSystem
 * @version 1.0
 */

class RuleCache {

    /** @var string 현재 파일 경로 */
    private $currentFile = __FILE__;

    /** @var int 캐시 TTL (초) */
    private $ttl;

    /** @var array 메모리 캐시 */
    private $memoryCache = [];

    /** @var string 파일 캐시 디렉토리 */
    private $cacheDir;

    /** @var bool 파일 캐시 사용 여부 */
    private $useFileCache;

    /** @var array 캐시 통계 */
    private $stats = [
        'hits' => 0,
        'misses' => 0,
        'writes' => 0
    ];

    /**
     * 생성자
     *
     * @param int $ttl 캐시 TTL (초, 기본 3600)
     * @param string $cacheDir 파일 캐시 디렉토리 (선택)
     */
    public function __construct(int $ttl = 3600, string $cacheDir = null) {
        $this->ttl = $ttl;
        $this->cacheDir = $cacheDir ?? __DIR__ . '/cache';
        $this->useFileCache = $this->initFileCache();
    }

    /**
     * 파일 캐시 디렉토리 초기화
     *
     * @return bool 사용 가능 여부
     */
    private function initFileCache(): bool {
        if (!is_dir($this->cacheDir)) {
            if (!@mkdir($this->cacheDir, 0755, true)) {
                error_log("[RuleCache] {$this->currentFile}:" . __LINE__ . " - 캐시 디렉토리 생성 실패: {$this->cacheDir}");
                return false;
            }
        }
        return is_writable($this->cacheDir);
    }

    /**
     * 캐시 키 생성
     *
     * @param string $filePath 파일 경로
     * @return string 캐시 키
     */
    private function generateKey(string $filePath): string {
        // 파일 경로 + 수정 시간으로 키 생성 (파일 변경 시 자동 무효화)
        $mtime = @filemtime($filePath);
        return md5($filePath . '_' . $mtime);
    }

    /**
     * 캐시에서 규칙 가져오기
     *
     * @param string $filePath 규칙 파일 경로
     * @return array|null 캐시된 규칙 또는 null
     */
    public function get(string $filePath): ?array {
        $key = $this->generateKey($filePath);

        // 1. 메모리 캐시 확인
        if (isset($this->memoryCache[$key])) {
            $cached = $this->memoryCache[$key];
            if ($this->isValid($cached)) {
                $this->stats['hits']++;
                return $cached['data'];
            }
            unset($this->memoryCache[$key]);
        }

        // 2. 파일 캐시 확인
        if ($this->useFileCache) {
            $cacheFile = $this->getCacheFilePath($key);
            if (file_exists($cacheFile)) {
                $content = @file_get_contents($cacheFile);
                if ($content !== false) {
                    $cached = @unserialize($content);
                    if ($cached !== false && $this->isValid($cached)) {
                        // 메모리 캐시에도 저장
                        $this->memoryCache[$key] = $cached;
                        $this->stats['hits']++;
                        return $cached['data'];
                    }
                    // 유효하지 않은 캐시 파일 삭제
                    @unlink($cacheFile);
                }
            }
        }

        $this->stats['misses']++;
        return null;
    }

    /**
     * 캐시에 규칙 저장
     *
     * @param string $filePath 규칙 파일 경로
     * @param array $rules 파싱된 규칙
     * @return bool 저장 성공 여부
     */
    public function set(string $filePath, array $rules): bool {
        $key = $this->generateKey($filePath);

        $cached = [
            'data' => $rules,
            'created_at' => time(),
            'expires_at' => time() + $this->ttl,
            'file_path' => $filePath,
            'file_mtime' => @filemtime($filePath)
        ];

        // 1. 메모리 캐시 저장
        $this->memoryCache[$key] = $cached;

        // 2. 파일 캐시 저장
        if ($this->useFileCache) {
            $cacheFile = $this->getCacheFilePath($key);
            $content = serialize($cached);
            if (@file_put_contents($cacheFile, $content, LOCK_EX) === false) {
                error_log("[RuleCache] {$this->currentFile}:" . __LINE__ . " - 파일 캐시 저장 실패: {$cacheFile}");
                return false;
            }
        }

        $this->stats['writes']++;
        return true;
    }

    /**
     * 캐시 유효성 확인
     *
     * @param array $cached 캐시된 데이터
     * @return bool 유효 여부
     */
    private function isValid(array $cached): bool {
        // 만료 시간 확인
        if (time() > $cached['expires_at']) {
            return false;
        }

        // 원본 파일 수정 시간 확인
        if (isset($cached['file_path']) && isset($cached['file_mtime'])) {
            $currentMtime = @filemtime($cached['file_path']);
            if ($currentMtime !== $cached['file_mtime']) {
                return false;
            }
        }

        return true;
    }

    /**
     * 캐시 파일 경로 반환
     *
     * @param string $key 캐시 키
     * @return string 파일 경로
     */
    private function getCacheFilePath(string $key): string {
        return $this->cacheDir . '/rules_' . $key . '.cache';
    }

    /**
     * 특정 캐시 무효화
     *
     * @param string $filePath 규칙 파일 경로
     * @return bool 성공 여부
     */
    public function invalidate(string $filePath): bool {
        $key = $this->generateKey($filePath);

        // 메모리 캐시 삭제
        unset($this->memoryCache[$key]);

        // 파일 캐시 삭제
        if ($this->useFileCache) {
            $cacheFile = $this->getCacheFilePath($key);
            if (file_exists($cacheFile)) {
                return @unlink($cacheFile);
            }
        }

        return true;
    }

    /**
     * 전체 캐시 초기화
     *
     * @return bool 성공 여부
     */
    public function clear(): bool {
        // 메모리 캐시 초기화
        $this->memoryCache = [];

        // 파일 캐시 초기화
        if ($this->useFileCache && is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/rules_*.cache');
            foreach ($files as $file) {
                @unlink($file);
            }
        }

        // 통계 초기화
        $this->stats = ['hits' => 0, 'misses' => 0, 'writes' => 0];

        return true;
    }

    /**
     * 만료된 캐시 정리
     *
     * @return int 삭제된 캐시 수
     */
    public function cleanup(): int {
        $deleted = 0;

        // 메모리 캐시 정리
        foreach ($this->memoryCache as $key => $cached) {
            if (!$this->isValid($cached)) {
                unset($this->memoryCache[$key]);
                $deleted++;
            }
        }

        // 파일 캐시 정리
        if ($this->useFileCache && is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/rules_*.cache');
            foreach ($files as $file) {
                $content = @file_get_contents($file);
                if ($content !== false) {
                    $cached = @unserialize($content);
                    if ($cached === false || !$this->isValid($cached)) {
                        @unlink($file);
                        $deleted++;
                    }
                }
            }
        }

        return $deleted;
    }

    /**
     * 캐시 상태 반환
     *
     * @return array 캐시 상태 정보
     */
    public function getStatus(): array {
        $memoryCount = count($this->memoryCache);
        $fileCount = 0;

        if ($this->useFileCache && is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/rules_*.cache');
            $fileCount = count($files);
        }

        $hitRate = 0;
        $total = $this->stats['hits'] + $this->stats['misses'];
        if ($total > 0) {
            $hitRate = round(($this->stats['hits'] / $total) * 100, 2);
        }

        return [
            'enabled' => true,
            'ttl' => $this->ttl,
            'file_cache_enabled' => $this->useFileCache,
            'cache_dir' => $this->cacheDir,
            'memory_cache_count' => $memoryCount,
            'file_cache_count' => $fileCount,
            'stats' => $this->stats,
            'hit_rate' => $hitRate . '%'
        ];
    }

    /**
     * TTL 설정
     *
     * @param int $ttl 새로운 TTL (초)
     */
    public function setTtl(int $ttl): void {
        $this->ttl = $ttl;
    }

    /**
     * TTL 반환
     *
     * @return int 현재 TTL
     */
    public function getTtl(): int {
        return $this->ttl;
    }

    /**
     * 메모리 캐시 크기 제한 적용
     *
     * @param int $maxItems 최대 아이템 수
     */
    public function limitMemoryCache(int $maxItems = 100): void {
        if (count($this->memoryCache) > $maxItems) {
            // 가장 오래된 항목부터 삭제 (FIFO)
            $excess = count($this->memoryCache) - $maxItems;
            $keys = array_keys($this->memoryCache);
            for ($i = 0; $i < $excess; $i++) {
                unset($this->memoryCache[$keys[$i]]);
            }
        }
    }

    /**
     * 캐시 워밍업 (사전 로드)
     *
     * @param array $filePaths 규칙 파일 경로 배열
     * @param callable $loader 규칙 로더 함수
     * @return int 로드된 캐시 수
     */
    public function warmup(array $filePaths, callable $loader): int {
        $loaded = 0;

        foreach ($filePaths as $filePath) {
            if (!file_exists($filePath)) {
                continue;
            }

            // 이미 캐시되어 있으면 스킵
            if ($this->get($filePath) !== null) {
                continue;
            }

            try {
                $rules = $loader($filePath);
                if ($rules !== null) {
                    $this->set($filePath, $rules);
                    $loaded++;
                }
            } catch (Exception $e) {
                error_log("[RuleCache] {$this->currentFile}:" . __LINE__ . " - 워밍업 실패: " . $e->getMessage());
            }
        }

        return $loaded;
    }
}

/*
 * 캐시 전략:
 * - 2단계 캐시: 메모리 → 파일
 * - 자동 무효화: 원본 파일 수정 시
 * - TTL 기반 만료: 기본 3600초 (1시간)
 * - LRU 정리: 메모리 캐시 크기 제한
 * - 캐시 워밍업: 애플리케이션 시작 시 사전 로드
 *
 * 파일 캐시 위치:
 * - engine/cache/rules_*.cache
 */
