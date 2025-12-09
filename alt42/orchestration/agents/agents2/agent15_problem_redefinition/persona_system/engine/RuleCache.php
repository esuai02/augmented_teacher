<?php
/**
 * RuleCache - 규칙 캐시 관리자
 *
 * 파싱된 규칙의 메모리/파일 캐시 관리
 * 성능 최적화를 위한 캐시 전략 구현
 *
 * @package Agent15_ProblemRedefinition
 * @version 1.0
 * @created 2025-12-02
 */

class RuleCache {

    /** @var int 기본 캐시 TTL (초) */
    const DEFAULT_TTL = 3600;

    /** @var string 캐시 디렉토리 */
    private $cacheDir;

    /** @var array 메모리 캐시 */
    private $memoryCache = [];

    /** @var array 캐시 메타데이터 */
    private $cacheMeta = [];

    /** @var bool 파일 캐시 활성화 여부 */
    private $fileCacheEnabled = true;

    /**
     * 생성자
     *
     * @param string|null $cacheDir 캐시 디렉토리 (null이면 기본 경로)
     */
    public function __construct($cacheDir = null) {
        $this->cacheDir = $cacheDir ?? sys_get_temp_dir() . '/agent15_rule_cache';
        $this->initCacheDir();
    }

    /**
     * 캐시 디렉토리 초기화
     */
    private function initCacheDir() {
        if (!is_dir($this->cacheDir)) {
            if (!@mkdir($this->cacheDir, 0755, true)) {
                $this->fileCacheEnabled = false;
                error_log("Failed to create cache directory: {$this->cacheDir} [" . __FILE__ . ":" . __LINE__ . "]");
            }
        }
    }

    /**
     * 캐시에서 데이터 조회
     *
     * @param string $key 캐시 키
     * @return mixed|null 캐시된 데이터 또는 null
     */
    public function get($key) {
        $cacheKey = $this->generateCacheKey($key);

        // 메모리 캐시 확인
        if ($this->isMemoryCacheValid($cacheKey)) {
            return $this->memoryCache[$cacheKey];
        }

        // 파일 캐시 확인
        if ($this->fileCacheEnabled) {
            $data = $this->getFromFileCache($cacheKey, $key);
            if ($data !== null) {
                // 메모리 캐시에도 저장
                $this->setMemoryCache($cacheKey, $data);
                return $data;
            }
        }

        return null;
    }

    /**
     * 캐시에 데이터 저장
     *
     * @param string $key 원본 키 (파일 경로 등)
     * @param mixed $data 저장할 데이터
     * @param int $ttl 유효 시간 (초)
     * @return bool 성공 여부
     */
    public function set($key, $data, $ttl = self::DEFAULT_TTL) {
        $cacheKey = $this->generateCacheKey($key);

        // 메모리 캐시 저장
        $this->setMemoryCache($cacheKey, $data, $ttl);

        // 파일 캐시 저장
        if ($this->fileCacheEnabled) {
            return $this->setFileCache($cacheKey, $key, $data, $ttl);
        }

        return true;
    }

    /**
     * 캐시 무효화
     *
     * @param string $key 캐시 키
     * @return bool 성공 여부
     */
    public function invalidate($key) {
        $cacheKey = $this->generateCacheKey($key);

        // 메모리 캐시 제거
        unset($this->memoryCache[$cacheKey]);
        unset($this->cacheMeta[$cacheKey]);

        // 파일 캐시 제거
        if ($this->fileCacheEnabled) {
            $filePath = $this->getCacheFilePath($cacheKey);
            if (file_exists($filePath)) {
                return @unlink($filePath);
            }
        }

        return true;
    }

    /**
     * 전체 캐시 초기화
     *
     * @return bool 성공 여부
     */
    public function clear() {
        // 메모리 캐시 초기화
        $this->memoryCache = [];
        $this->cacheMeta = [];

        // 파일 캐시 초기화
        if ($this->fileCacheEnabled && is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/*.cache');
            foreach ($files as $file) {
                @unlink($file);
            }
        }

        return true;
    }

    /**
     * 캐시 키 생성
     *
     * @param string $key 원본 키
     * @return string 생성된 캐시 키
     */
    private function generateCacheKey($key) {
        return 'agent15_' . md5($key);
    }

    /**
     * 메모리 캐시 유효성 확인
     *
     * @param string $cacheKey 캐시 키
     * @return bool 유효 여부
     */
    private function isMemoryCacheValid($cacheKey) {
        if (!isset($this->memoryCache[$cacheKey]) || !isset($this->cacheMeta[$cacheKey])) {
            return false;
        }

        $meta = $this->cacheMeta[$cacheKey];
        return time() < ($meta['created'] + $meta['ttl']);
    }

    /**
     * 메모리 캐시 저장
     *
     * @param string $cacheKey 캐시 키
     * @param mixed $data 데이터
     * @param int $ttl TTL
     */
    private function setMemoryCache($cacheKey, $data, $ttl = self::DEFAULT_TTL) {
        $this->memoryCache[$cacheKey] = $data;
        $this->cacheMeta[$cacheKey] = [
            'created' => time(),
            'ttl' => $ttl
        ];
    }

    /**
     * 파일 캐시 경로 반환
     *
     * @param string $cacheKey 캐시 키
     * @return string 파일 경로
     */
    private function getCacheFilePath($cacheKey) {
        return $this->cacheDir . '/' . $cacheKey . '.cache';
    }

    /**
     * 파일 캐시에서 조회
     *
     * @param string $cacheKey 캐시 키
     * @param string $originalKey 원본 키
     * @return mixed|null 데이터 또는 null
     */
    private function getFromFileCache($cacheKey, $originalKey) {
        $filePath = $this->getCacheFilePath($cacheKey);

        if (!file_exists($filePath)) {
            return null;
        }

        try {
            $content = file_get_contents($filePath);
            $cached = unserialize($content);

            if (!is_array($cached) || !isset($cached['data']) || !isset($cached['meta'])) {
                return null;
            }

            // TTL 확인
            $meta = $cached['meta'];
            if (time() >= ($meta['created'] + $meta['ttl'])) {
                @unlink($filePath);
                return null;
            }

            // 원본 파일 변경 확인 (파일 경로인 경우)
            if (file_exists($originalKey)) {
                $fileModTime = filemtime($originalKey);
                if ($fileModTime > $meta['created']) {
                    @unlink($filePath);
                    return null;
                }
            }

            return $cached['data'];

        } catch (Exception $e) {
            error_log("Cache read error: " . $e->getMessage() . " [" . __FILE__ . ":" . __LINE__ . "]");
            return null;
        }
    }

    /**
     * 파일 캐시에 저장
     *
     * @param string $cacheKey 캐시 키
     * @param string $originalKey 원본 키
     * @param mixed $data 데이터
     * @param int $ttl TTL
     * @return bool 성공 여부
     */
    private function setFileCache($cacheKey, $originalKey, $data, $ttl) {
        $filePath = $this->getCacheFilePath($cacheKey);

        try {
            $cached = [
                'data' => $data,
                'meta' => [
                    'created' => time(),
                    'ttl' => $ttl,
                    'original_key' => $originalKey
                ]
            ];

            $content = serialize($cached);
            return file_put_contents($filePath, $content, LOCK_EX) !== false;

        } catch (Exception $e) {
            error_log("Cache write error: " . $e->getMessage() . " [" . __FILE__ . ":" . __LINE__ . "]");
            return false;
        }
    }

    /**
     * 캐시 통계 조회
     *
     * @return array 통계 정보
     */
    public function getStats() {
        $stats = [
            'memory_cache_count' => count($this->memoryCache),
            'file_cache_enabled' => $this->fileCacheEnabled,
            'file_cache_count' => 0,
            'cache_dir' => $this->cacheDir
        ];

        if ($this->fileCacheEnabled && is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/*.cache');
            $stats['file_cache_count'] = count($files);
        }

        return $stats;
    }

    /**
     * 만료된 캐시 정리
     *
     * @return int 정리된 항목 수
     */
    public function cleanup() {
        $cleaned = 0;

        // 메모리 캐시 정리
        foreach ($this->cacheMeta as $key => $meta) {
            if (time() >= ($meta['created'] + $meta['ttl'])) {
                unset($this->memoryCache[$key]);
                unset($this->cacheMeta[$key]);
                $cleaned++;
            }
        }

        // 파일 캐시 정리
        if ($this->fileCacheEnabled && is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/*.cache');
            foreach ($files as $file) {
                try {
                    $content = file_get_contents($file);
                    $cached = unserialize($content);

                    if (!is_array($cached) || !isset($cached['meta'])) {
                        @unlink($file);
                        $cleaned++;
                        continue;
                    }

                    $meta = $cached['meta'];
                    if (time() >= ($meta['created'] + $meta['ttl'])) {
                        @unlink($file);
                        $cleaned++;
                    }
                } catch (Exception $e) {
                    @unlink($file);
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }
}
