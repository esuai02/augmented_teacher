<?php
/**
 * JsonEncoder - 안전한 JSON 인코딩을 위한 유틸리티 클래스
 * 
 * UTF-8 검증 및 정제, 서로게이트 쌍 제거, 제어 문자 필터링을 통해
 * JSON 인코딩 실패를 방지하고 안전한 데이터 처리를 보장합니다.
 * 
 * @package    utils
 * @subpackage JsonEncoder
 * @author     ALT42 Development Team
 * @version    1.0.0
 * @since      PHP 7.4+
 * 
 * 주요 기능:
 * - UTF-8 유효성 검증 및 복구
 * - 서로게이트 쌍(U+D800-U+DFFF) 제거
 * - 제어 문자 및 특수 문자 필터링
 * - BOM(Byte Order Mark) 제거
 * - 상세한 오류 보고 및 로깅
 * 
 * 사용 예시:
 * $safeJson = JsonEncoder::encode($data);
 * $safeJson = JsonEncoder::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
 */

class JsonEncoder {
    
    /**
     * 인코딩 옵션 상수
     */
    const DEFAULT_ENCODING_OPTIONS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    const SAFE_ENCODING_OPTIONS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;
    
    /**
     * 정제 레벨 상수
     */
    const SANITIZE_NONE = 0;        // 정제 없음
    const SANITIZE_MINIMAL = 1;      // 최소 정제 (BOM 제거만)
    const SANITIZE_STANDARD = 2;     // 표준 정제 (UTF-8 검증, BOM 제거)
    const SANITIZE_AGGRESSIVE = 3;   // 적극적 정제 (모든 문제 해결)
    
    /**
     * 오류 코드 상수
     */
    const ERROR_NONE = 0;
    const ERROR_INVALID_UTF8 = 1;
    const ERROR_SURROGATE_FOUND = 2;
    const ERROR_CONTROL_CHARS = 4;
    const ERROR_BOM_FOUND = 8;
    const ERROR_ENCODING_FAILED = 16;
    const ERROR_RECURSION_DEPTH = 32;
    const ERROR_INF_OR_NAN = 64;
    const ERROR_UNSUPPORTED_TYPE = 128;
    
    /**
     * 문자 범위 상수
     */
    const SURROGATE_MIN = 0xD800;
    const SURROGATE_MAX = 0xDFFF;
    const HIGH_SURROGATE_MIN = 0xD800;
    const HIGH_SURROGATE_MAX = 0xDBFF;
    const LOW_SURROGATE_MIN = 0xDC00;
    const LOW_SURROGATE_MAX = 0xDFFF;
    
    /**
     * BOM (Byte Order Mark) 패턴
     */
    const BOM_UTF8 = "\xEF\xBB\xBF";
    const BOM_UTF16_BE = "\xFE\xFF";
    const BOM_UTF16_LE = "\xFF\xFE";
    const BOM_UTF32_BE = "\x00\x00\xFE\xFF";
    const BOM_UTF32_LE = "\xFF\xFE\x00\x00";
    
    /**
     * 설정 저장용 정적 속성
     */
    protected static $config = [
        'sanitize_level' => self::SANITIZE_STANDARD,
        'preserve_newlines' => true,
        'preserve_tabs' => true,
        'preserve_carriage_returns' => true,
        'log_errors' => true,
        'throw_on_error' => false,
        'max_depth' => 512,
        'encoding_options' => self::DEFAULT_ENCODING_OPTIONS,
        'fallback_encoding' => 'UTF-8//IGNORE',
        'detect_encodings' => ['UTF-8', 'EUC-KR', 'Windows-949', 'ISO-8859-1'],
        'remove_zero_width' => true,
        'remove_invisible' => true,
        'strict_mode' => false
    ];
    
    /**
     * 마지막 오류 정보 저장
     */
    protected static $lastError = [
        'code' => self::ERROR_NONE,
        'message' => '',
        'details' => [],
        'sanitization_report' => []
    ];
    
    /**
     * 제어 문자 화이트리스트
     * 교육 콘텐츠에서 자주 사용되는 포맷팅 문자들
     */
    protected static $controlCharWhitelist = [
        "\t",   // 탭 (0x09)
        "\n",   // 줄바꿈 (0x0A)
        "\r",   // 캐리지 리턴 (0x0D)
    ];
    
    /**
     * 영너비 문자 목록
     * 텍스트에서 제거해야 할 보이지 않는 문자들
     */
    protected static $zeroWidthChars = [
        "\u{200B}",  // Zero Width Space
        "\u{200C}",  // Zero Width Non-Joiner
        "\u{200D}",  // Zero Width Joiner
        "\u{200E}",  // Left-To-Right Mark
        "\u{200F}",  // Right-To-Left Mark
        "\u{202A}",  // Left-To-Right Embedding
        "\u{202B}",  // Right-To-Left Embedding
        "\u{202C}",  // Pop Directional Formatting
        "\u{202D}",  // Left-To-Right Override
        "\u{202E}",  // Right-To-Left Override
        "\u{2060}",  // Word Joiner
        "\u{2061}",  // Function Application
        "\u{2062}",  // Invisible Times
        "\u{2063}",  // Invisible Separator
        "\u{2064}",  // Invisible Plus
        "\u{206A}",  // Inhibit Symmetric Swapping
        "\u{206B}",  // Activate Symmetric Swapping
        "\u{206C}",  // Inhibit Arabic Form Shaping
        "\u{206D}",  // Activate Arabic Form Shaping
        "\u{206E}",  // National Digit Shapes
        "\u{206F}",  // Nominal Digit Shapes
        "\u{FEFF}",  // Zero Width No-Break Space (BOM)
        "\u{FFF9}",  // Interlinear Annotation Anchor
        "\u{FFFA}",  // Interlinear Annotation Separator
        "\u{FFFB}",  // Interlinear Annotation Terminator
    ];
    
    /**
     * 설정 구성
     * 
     * @param array $config 설정 배열
     * @return void
     */
    public static function configure(array $config): void {
        self::$config = array_merge(self::$config, $config);
    }
    
    /**
     * 단일 설정 값 가져오기
     * 
     * @param string $key 설정 키
     * @param mixed $default 기본값
     * @return mixed
     */
    public static function getConfig(string $key, $default = null) {
        return self::$config[$key] ?? $default;
    }
    
    /**
     * 마지막 오류 정보 가져오기
     * 
     * @return array
     */
    public static function getLastError(): array {
        return self::$lastError;
    }
    
    /**
     * 마지막 오류 코드 가져오기
     * 
     * @return int
     */
    public static function getLastErrorCode(): int {
        return self::$lastError['code'];
    }
    
    /**
     * 마지막 오류 메시지 가져오기
     * 
     * @return string
     */
    public static function getLastErrorMessage(): string {
        return self::$lastError['message'];
    }
    
    /**
     * 오류 초기화
     * 
     * @return void
     */
    public static function clearError(): void {
        self::$lastError = [
            'code' => self::ERROR_NONE,
            'message' => '',
            'details' => [],
            'sanitization_report' => []
        ];
    }
    
    /**
     * 오류 설정
     * 
     * @param int $code 오류 코드
     * @param string $message 오류 메시지
     * @param array $details 상세 정보
     * @return void
     */
    protected static function setError(int $code, string $message, array $details = []): void {
        self::$lastError['code'] |= $code;
        self::$lastError['message'] = $message;
        self::$lastError['details'] = array_merge(self::$lastError['details'], $details);
        
        if (self::$config['log_errors']) {
            self::logError($code, $message, $details);
        }
        
        if (self::$config['throw_on_error']) {
            throw new JsonEncoderException($message, $code);
        }
    }
    
    /**
     * 오류 로깅
     * 
     * @param int $code 오류 코드
     * @param string $message 오류 메시지
     * @param array $details 상세 정보
     * @return void
     */
    protected static function logError(int $code, string $message, array $details = []): void {
        // 로깅 구현 (프로젝트의 Logger 클래스 사용 가능시 통합)
        error_log(sprintf(
            "[JsonEncoder] Error %d: %s - Details: %s",
            $code,
            $message,
            json_encode($details, JSON_UNESCAPED_UNICODE)
        ));
    }
    
    /**
     * 정제 보고서에 항목 추가
     * 
     * @param string $type 정제 유형
     * @param string $description 설명
     * @param mixed $data 관련 데이터
     * @return void
     */
    protected static function addToSanitizationReport(string $type, string $description, $data = null): void {
        self::$lastError['sanitization_report'][] = [
            'type' => $type,
            'description' => $description,
            'data' => $data,
            'timestamp' => microtime(true)
        ];
    }
    
    // 플레이스홀더 메서드들 - 다음 태스크에서 구현
    
    /**
     * 메인 인코딩 메서드 (태스크 5에서 구현)
     */
    public static function encode($data, int $options = null, int $depth = 512): string {
        // 구현 예정
        return json_encode($data, $options ?? self::$config['encoding_options'], $depth);
    }
    
    /**
     * UTF-8 유효성 검증
     * 
     * 문자열이 유효한 UTF-8 인코딩인지 검증합니다.
     * 멀티바이트 시퀀스의 유효성도 체크합니다.
     * 
     * @param string $string 검증할 문자열
     * @return bool 유효한 UTF-8이면 true
     */
    public static function validateUtf8(string $string): bool {
        // 빠른 검증을 위해 먼저 mb_check_encoding 사용
        if (!mb_check_encoding($string, 'UTF-8')) {
            self::setError(
                self::ERROR_INVALID_UTF8,
                'Invalid UTF-8 encoding detected',
                ['length' => strlen($string)]
            );
            return false;
        }
        
        // 바이트 레벨 검증 (더 엄격한 검증)
        $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $byte = ord($string[$i]);
            
            if ($byte < 0x80) {
                // ASCII (0xxxxxxx)
                continue;
            } elseif (($byte & 0xE0) === 0xC0) {
                // 2-byte sequence (110xxxxx 10xxxxxx)
                if ($i + 1 >= $len || (ord($string[$i + 1]) & 0xC0) !== 0x80) {
                    self::setError(
                        self::ERROR_INVALID_UTF8,
                        'Invalid 2-byte UTF-8 sequence',
                        ['position' => $i, 'byte' => dechex($byte)]
                    );
                    return false;
                }
                // 오버롱 인코딩 체크
                if ($byte < 0xC2) {
                    self::setError(
                        self::ERROR_INVALID_UTF8,
                        'Overlong 2-byte UTF-8 sequence',
                        ['position' => $i, 'byte' => dechex($byte)]
                    );
                    return false;
                }
                $i++;
            } elseif (($byte & 0xF0) === 0xE0) {
                // 3-byte sequence (1110xxxx 10xxxxxx 10xxxxxx)
                if ($i + 2 >= $len || 
                    (ord($string[$i + 1]) & 0xC0) !== 0x80 ||
                    (ord($string[$i + 2]) & 0xC0) !== 0x80) {
                    self::setError(
                        self::ERROR_INVALID_UTF8,
                        'Invalid 3-byte UTF-8 sequence',
                        ['position' => $i, 'byte' => dechex($byte)]
                    );
                    return false;
                }
                // 오버롱 인코딩 체크
                $byte2 = ord($string[$i + 1]);
                if ($byte === 0xE0 && $byte2 < 0xA0) {
                    self::setError(
                        self::ERROR_INVALID_UTF8,
                        'Overlong 3-byte UTF-8 sequence',
                        ['position' => $i]
                    );
                    return false;
                }
                $i += 2;
            } elseif (($byte & 0xF8) === 0xF0) {
                // 4-byte sequence (11110xxx 10xxxxxx 10xxxxxx 10xxxxxx)
                if ($i + 3 >= $len ||
                    (ord($string[$i + 1]) & 0xC0) !== 0x80 ||
                    (ord($string[$i + 2]) & 0xC0) !== 0x80 ||
                    (ord($string[$i + 3]) & 0xC0) !== 0x80) {
                    self::setError(
                        self::ERROR_INVALID_UTF8,
                        'Invalid 4-byte UTF-8 sequence',
                        ['position' => $i, 'byte' => dechex($byte)]
                    );
                    return false;
                }
                // 오버롱 인코딩 체크
                $byte2 = ord($string[$i + 1]);
                if ($byte === 0xF0 && $byte2 < 0x90) {
                    self::setError(
                        self::ERROR_INVALID_UTF8,
                        'Overlong 4-byte UTF-8 sequence',
                        ['position' => $i]
                    );
                    return false;
                }
                // UTF-8 범위 체크 (최대 U+10FFFF)
                if ($byte === 0xF4 && $byte2 > 0x8F) {
                    self::setError(
                        self::ERROR_INVALID_UTF8,
                        'UTF-8 codepoint exceeds U+10FFFF',
                        ['position' => $i]
                    );
                    return false;
                }
                $i += 3;
            } else {
                // 유효하지 않은 UTF-8 시작 바이트
                self::setError(
                    self::ERROR_INVALID_UTF8,
                    'Invalid UTF-8 start byte',
                    ['position' => $i, 'byte' => dechex($byte)]
                );
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * UTF-8 정제
     * 
     * 잘못된 UTF-8 시퀀스를 제거하거나 수정합니다.
     * 유효한 멀티바이트 문자는 보존합니다.
     * 
     * @param string $string 정제할 문자열
     * @return string 정제된 UTF-8 문자열
     */
    public static function cleanUtf8(string $string): string {
        if (empty($string)) {
            return $string;
        }
        
        // 먼저 인코딩 감지 시도
        $detectedEncoding = self::detectEncoding($string);
        
        // UTF-8이 아닌 경우 변환 시도
        if ($detectedEncoding && $detectedEncoding !== 'UTF-8') {
            $converted = self::convertToUtf8($string, $detectedEncoding);
            if ($converted !== false) {
                self::addToSanitizationReport(
                    'encoding_conversion',
                    "Converted from {$detectedEncoding} to UTF-8",
                    ['original_encoding' => $detectedEncoding]
                );
                $string = $converted;
            }
        }
        
        // 잘못된 UTF-8 시퀀스 제거
        $cleaned = self::fixMalformedUtf8($string);
        
        if ($cleaned !== $string) {
            self::addToSanitizationReport(
                'utf8_cleaning',
                'Removed or fixed malformed UTF-8 sequences',
                ['original_length' => strlen($string), 'cleaned_length' => strlen($cleaned)]
            );
        }
        
        return $cleaned;
    }
    
    /**
     * 인코딩 감지
     * 
     * 문자열의 인코딩을 자동으로 감지합니다.
     * 한국어 교육 콘텐츠에서 자주 사용되는 인코딩을 우선 체크합니다.
     * 
     * @param string $string 감지할 문자열
     * @return string|false 감지된 인코딩 또는 false
     */
    public static function detectEncoding(string $string) {
        // mb_detect_encoding을 사용하여 감지
        $encoding = mb_detect_encoding(
            $string,
            self::$config['detect_encodings'],
            true // strict mode
        );
        
        if ($encoding === false) {
            // 휴리스틱 방법으로 추가 감지 시도
            // EUC-KR 특징적인 바이트 패턴 체크
            if (self::isLikelyEucKr($string)) {
                return 'EUC-KR';
            }
            // Windows-949 체크
            if (self::isLikelyWindows949($string)) {
                return 'Windows-949';
            }
        }
        
        return $encoding;
    }
    
    /**
     * EUC-KR 인코딩 가능성 체크
     * 
     * @param string $string 체크할 문자열
     * @return bool
     */
    protected static function isLikelyEucKr(string $string): bool {
        $len = strlen($string);
        $eucKrCount = 0;
        
        for ($i = 0; $i < $len - 1; $i++) {
            $byte1 = ord($string[$i]);
            $byte2 = ord($string[$i + 1]);
            
            // EUC-KR 2바이트 문자 범위
            if ($byte1 >= 0xA1 && $byte1 <= 0xFE &&
                $byte2 >= 0xA1 && $byte2 <= 0xFE) {
                $eucKrCount++;
                $i++; // 다음 바이트 건너뛰기
            }
        }
        
        // 전체 길이 대비 EUC-KR 패턴 비율
        return ($eucKrCount * 2) > ($len * 0.3);
    }
    
    /**
     * Windows-949 인코딩 가능성 체크
     * 
     * @param string $string 체크할 문자열
     * @return bool
     */
    protected static function isLikelyWindows949(string $string): bool {
        // Windows-949는 EUC-KR의 확장이므로 비슷한 패턴
        // 추가적인 코드 범위 체크
        $len = strlen($string);
        $cp949Count = 0;
        
        for ($i = 0; $i < $len - 1; $i++) {
            $byte1 = ord($string[$i]);
            $byte2 = ord($string[$i + 1]);
            
            // CP949 확장 범위
            if (($byte1 >= 0x81 && $byte1 <= 0xC6) &&
                (($byte2 >= 0x41 && $byte2 <= 0x5A) ||
                 ($byte2 >= 0x61 && $byte2 <= 0x7A) ||
                 ($byte2 >= 0x81 && $byte2 <= 0xFE))) {
                $cp949Count++;
                $i++;
            }
        }
        
        return ($cp949Count * 2) > ($len * 0.3);
    }
    
    /**
     * UTF-8로 변환
     * 
     * @param string $string 변환할 문자열
     * @param string $fromEncoding 원본 인코딩
     * @return string|false 변환된 문자열 또는 실패시 false
     */
    protected static function convertToUtf8(string $string, string $fromEncoding) {
        // mb_convert_encoding 사용
        $converted = @mb_convert_encoding($string, 'UTF-8', $fromEncoding);
        
        if ($converted === false) {
            // iconv 시도
            $converted = @iconv($fromEncoding, 'UTF-8//IGNORE', $string);
        }
        
        return $converted;
    }
    
    /**
     * 잘못된 UTF-8 수정
     * 
     * 잘못된 UTF-8 시퀀스를 제거하거나 대체 문자로 치환합니다.
     * 
     * @param string $string 수정할 문자열
     * @return string 수정된 문자열
     */
    protected static function fixMalformedUtf8(string $string): string {
        // 여러 방법을 시도하여 최선의 결과 얻기
        
        // 방법 1: mb_convert_encoding으로 정제
        $cleaned = @mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        if ($cleaned !== false && self::validateUtf8($cleaned)) {
            return $cleaned;
        }
        
        // 방법 2: iconv으로 정제 (IGNORE 옵션)
        $cleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $string);
        if ($cleaned !== false && self::validateUtf8($cleaned)) {
            return $cleaned;
        }
        
        // 방법 3: 정규식으로 유효하지 않은 시퀀스 제거
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);
        if ($cleaned !== null) {
            // 추가로 유효하지 않은 UTF-8 시퀀스 제거
            $cleaned = preg_replace('/[\x80-\xFF](?![\x80-\xBF])/u', '', $cleaned);
            if ($cleaned !== null && self::validateUtf8($cleaned)) {
                return $cleaned;
            }
        }
        
        // 방법 4: 바이트 단위로 재구성
        return self::reconstructUtf8($string);
    }
    
    /**
     * UTF-8 재구성
     * 
     * 바이트 단위로 분석하여 유효한 UTF-8만 재구성합니다.
     * 
     * @param string $string 재구성할 문자열
     * @return string 재구성된 문자열
     */
    protected static function reconstructUtf8(string $string): string {
        $result = '';
        $len = strlen($string);
        
        for ($i = 0; $i < $len; $i++) {
            $byte = ord($string[$i]);
            
            if ($byte < 0x80) {
                // ASCII
                $result .= chr($byte);
            } elseif (($byte & 0xE0) === 0xC0) {
                // 2-byte sequence
                if ($i + 1 < $len && (ord($string[$i + 1]) & 0xC0) === 0x80) {
                    $result .= substr($string, $i, 2);
                    $i++;
                }
            } elseif (($byte & 0xF0) === 0xE0) {
                // 3-byte sequence
                if ($i + 2 < $len &&
                    (ord($string[$i + 1]) & 0xC0) === 0x80 &&
                    (ord($string[$i + 2]) & 0xC0) === 0x80) {
                    $result .= substr($string, $i, 3);
                    $i += 2;
                }
            } elseif (($byte & 0xF8) === 0xF0) {
                // 4-byte sequence
                if ($i + 3 < $len &&
                    (ord($string[$i + 1]) & 0xC0) === 0x80 &&
                    (ord($string[$i + 2]) & 0xC0) === 0x80 &&
                    (ord($string[$i + 3]) & 0xC0) === 0x80) {
                    $result .= substr($string, $i, 4);
                    $i += 3;
                }
            }
            // 유효하지 않은 바이트는 건너뛰기
        }
        
        return $result;
    }
    
    /**
     * 서로게이트 쌍 감지
     * 
     * 문자열에서 서로게이트 쌍(U+D800-U+DFFF)을 감지합니다.
     * 
     * @param string $string 감지할 문자열
     * @return array 감지된 서로게이트 정보 [위치 => 타입]
     */
    public static function detectSurrogatePairs(string $string): array {
        $surrogates = [];
        $len = strlen($string);
        
        // UTF-8로 인코딩된 서로게이트 범위 패턴
        // UTF-8에서 서로게이트는 원칙적으로 허용되지 않지만
        // 잘못된 변환이나 JavaScript에서 온 데이터에서 나타날 수 있음
        
        // 정규식으로 감지 (UTF-8로 잘못 인코딩된 서로게이트)
        // ED A0 80 - ED AF BF: High surrogates (D800-DBFF)
        // ED B0 80 - ED BF BF: Low surrogates (DC00-DFFF)
        
        for ($i = 0; $i < $len - 2; $i++) {
            $byte1 = ord($string[$i]);
            
            // UTF-8로 인코딩된 서로게이트 체크 (3바이트 시퀀스)
            if ($byte1 === 0xED) {
                $byte2 = ord($string[$i + 1]);
                $byte3 = ord($string[$i + 2]);
                
                // High surrogate (D800-DBFF)
                if ($byte2 >= 0xA0 && $byte2 <= 0xAF) {
                    $codepoint = (($byte1 & 0x0F) << 12) | (($byte2 & 0x3F) << 6) | ($byte3 & 0x3F);
                    if ($codepoint >= self::SURROGATE_MIN && $codepoint <= self::HIGH_SURROGATE_MAX) {
                        $surrogates[$i] = [
                            'type' => 'high',
                            'codepoint' => $codepoint,
                            'hex' => sprintf('U+%04X', $codepoint),
                            'bytes' => [$byte1, $byte2, $byte3]
                        ];
                    }
                }
                // Low surrogate (DC00-DFFF)
                elseif ($byte2 >= 0xB0 && $byte2 <= 0xBF) {
                    $codepoint = (($byte1 & 0x0F) << 12) | (($byte2 & 0x3F) << 6) | ($byte3 & 0x3F);
                    if ($codepoint >= self::LOW_SURROGATE_MIN && $codepoint <= self::SURROGATE_MAX) {
                        $surrogates[$i] = [
                            'type' => 'low',
                            'codepoint' => $codepoint,
                            'hex' => sprintf('U+%04X', $codepoint),
                            'bytes' => [$byte1, $byte2, $byte3]
                        ];
                    }
                }
            }
        }
        
        // 추가로 정규식 패턴 사용
        $pattern = '/[\x{D800}-\x{DFFF}]/u';
        if (@preg_match_all($pattern, $string, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $position = $match[1];
                if (!isset($surrogates[$position])) {
                    $char = $match[0];
                    $codepoint = mb_ord($char);
                    $surrogates[$position] = [
                        'type' => $codepoint <= 0xDBFF ? 'high' : 'low',
                        'codepoint' => $codepoint,
                        'hex' => sprintf('U+%04X', $codepoint),
                        'char' => $char
                    ];
                }
            }
        }
        
        return $surrogates;
    }
    
    /**
     * 서로게이트 쌍 유효성 검증
     * 
     * 서로게이트 쌍이 올바르게 짝을 이루고 있는지 검증합니다.
     * 
     * @param string $string 검증할 문자열
     * @return array 유효성 검증 결과
     */
    public static function validateSurrogatePairs(string $string): array {
        $surrogates = self::detectSurrogatePairs($string);
        $validation = [
            'valid' => true,
            'unpaired_high' => [],
            'unpaired_low' => [],
            'valid_pairs' => [],
            'total_surrogates' => count($surrogates)
        ];
        
        if (empty($surrogates)) {
            return $validation;
        }
        
        $positions = array_keys($surrogates);
        sort($positions);
        
        $i = 0;
        while ($i < count($positions)) {
            $pos = $positions[$i];
            $current = $surrogates[$pos];
            
            if ($current['type'] === 'high') {
                // High surrogate 다음에 Low surrogate가 와야 함
                $nextPos = $positions[$i + 1] ?? null;
                
                if ($nextPos !== null && 
                    $nextPos === $pos + 3 && // 3바이트 간격
                    $surrogates[$nextPos]['type'] === 'low') {
                    // 유효한 쌍
                    $validation['valid_pairs'][] = [
                        'high' => $current,
                        'low' => $surrogates[$nextPos],
                        'position' => $pos
                    ];
                    $i += 2; // 쌍으로 처리했으므로 2개 건너뛰기
                } else {
                    // 짝이 없는 high surrogate
                    $validation['unpaired_high'][] = $current;
                    $validation['valid'] = false;
                    $i++;
                }
            } else {
                // 짝이 없는 low surrogate
                $validation['unpaired_low'][] = $current;
                $validation['valid'] = false;
                $i++;
            }
        }
        
        return $validation;
    }
    
    /**
     * 서로게이트 쌍 제거
     * 
     * 유효하지 않은 서로게이트 쌍을 제거합니다.
     * 올바른 쌍은 적절한 UTF-8 문자로 변환합니다.
     * 
     * @param string $string 처리할 문자열
     * @return string 서로게이트가 제거된 문자열
     */
    public static function removeSurrogatePairs(string $string): string {
        if (empty($string)) {
            return $string;
        }
        
        $validation = self::validateSurrogatePairs($string);
        
        // 서로게이트가 없으면 원본 반환
        if ($validation['total_surrogates'] === 0) {
            return $string;
        }
        
        // 서로게이트 제거 수행
        $result = $string;
        $removed = 0;
        
        // 먼저 짝이 없는 서로게이트 제거
        foreach (array_merge($validation['unpaired_high'], $validation['unpaired_low']) as $unpaired) {
            if (isset($unpaired['bytes'])) {
                // 바이트 시퀀스로 제거
                $sequence = chr($unpaired['bytes'][0]) . chr($unpaired['bytes'][1]) . chr($unpaired['bytes'][2]);
                $result = str_replace($sequence, '', $result);
                $removed++;
            }
        }
        
        // 유효한 쌍은 적절한 문자로 변환 (필요한 경우)
        foreach ($validation['valid_pairs'] as $pair) {
            // 서로게이트 쌍을 실제 유니코드 코드포인트로 변환
            $highSurrogate = $pair['high']['codepoint'];
            $lowSurrogate = $pair['low']['codepoint'];
            
            // 서로게이트 쌍에서 실제 코드포인트 계산
            $codepoint = 0x10000 + (($highSurrogate - 0xD800) * 0x400) + ($lowSurrogate - 0xDC00);
            
            // UTF-8로 인코딩
            if ($codepoint <= 0x10FFFF) {
                $utf8Char = self::codepointToUtf8($codepoint);
                
                if (isset($pair['high']['bytes']) && isset($pair['low']['bytes'])) {
                    $surrogateSequence = 
                        chr($pair['high']['bytes'][0]) . chr($pair['high']['bytes'][1]) . chr($pair['high']['bytes'][2]) .
                        chr($pair['low']['bytes'][0]) . chr($pair['low']['bytes'][1]) . chr($pair['low']['bytes'][2]);
                    $result = str_replace($surrogateSequence, $utf8Char, $result);
                }
            }
        }
        
        // 정규식으로 추가 제거 (남은 서로게이트)
        $result = preg_replace('/[\x{D800}-\x{DFFF}]/u', '', $result);
        
        // UTF-8로 잘못 인코딩된 서로게이트 패턴 제거
        // ED A0 80 - ED BF BF 범위
        $pattern = '/\xED[\xA0-\xBF][\x80-\xBF]/';
        $result = preg_replace($pattern, '', $result);
        
        if ($removed > 0 || $result !== $string) {
            self::addToSanitizationReport(
                'surrogate_removal',
                sprintf('Removed %d unpaired surrogates, converted %d valid pairs', 
                    count($validation['unpaired_high']) + count($validation['unpaired_low']),
                    count($validation['valid_pairs'])),
                $validation
            );
            
            self::setError(
                self::ERROR_SURROGATE_FOUND,
                'Surrogate pairs detected and removed',
                ['removed' => $removed, 'validation' => $validation]
            );
        }
        
        return $result;
    }
    
    /**
     * 짝이 없는 서로게이트 수정
     * 
     * JavaScript에서 온 데이터의 일반적인 서로게이트 문제를 수정합니다.
     * 
     * @param string $string 수정할 문자열
     * @return string 수정된 문자열
     */
    public static function fixUnpairedSurrogates(string $string): string {
        $validation = self::validateSurrogatePairs($string);
        
        if ($validation['valid']) {
            return $string;
        }
        
        // 짝이 없는 서로게이트를 대체 문자(�)로 치환
        $result = $string;
        
        // High surrogates without pair
        foreach ($validation['unpaired_high'] as $unpaired) {
            if (isset($unpaired['bytes'])) {
                $sequence = chr($unpaired['bytes'][0]) . chr($unpaired['bytes'][1]) . chr($unpaired['bytes'][2]);
                $result = str_replace($sequence, "\xEF\xBF\xBD", $result); // U+FFFD Replacement Character
            }
        }
        
        // Low surrogates without pair  
        foreach ($validation['unpaired_low'] as $unpaired) {
            if (isset($unpaired['bytes'])) {
                $sequence = chr($unpaired['bytes'][0]) . chr($unpaired['bytes'][1]) . chr($unpaired['bytes'][2]);
                $result = str_replace($sequence, "\xEF\xBF\xBD", $result); // U+FFFD Replacement Character
            }
        }
        
        return $result;
    }
    
    /**
     * 유니코드 코드포인트를 UTF-8로 변환
     * 
     * @param int $codepoint 유니코드 코드포인트
     * @return string UTF-8 문자
     */
    protected static function codepointToUtf8(int $codepoint): string {
        if ($codepoint <= 0x7F) {
            return chr($codepoint);
        } elseif ($codepoint <= 0x7FF) {
            return chr(0xC0 | ($codepoint >> 6)) .
                   chr(0x80 | ($codepoint & 0x3F));
        } elseif ($codepoint <= 0xFFFF) {
            return chr(0xE0 | ($codepoint >> 12)) .
                   chr(0x80 | (($codepoint >> 6) & 0x3F)) .
                   chr(0x80 | ($codepoint & 0x3F));
        } elseif ($codepoint <= 0x10FFFF) {
            return chr(0xF0 | ($codepoint >> 18)) .
                   chr(0x80 | (($codepoint >> 12) & 0x3F)) .
                   chr(0x80 | (($codepoint >> 6) & 0x3F)) .
                   chr(0x80 | ($codepoint & 0x3F));
        }
        
        return '';
    }
    
    /**
     * 제어 문자 제거 (태스크 4에서 구현)
     */
    public static function removeControlCharacters(string $string): string {
        // 구현 예정
        return $string;
    }
    
    /**
     * BOM 제거 (태스크 4에서 구현)
     */
    public static function removeBOM(string $string): string {
        // 구현 예정
        return $string;
    }
    
    /**
     * 영너비 문자 제거 (태스크 4에서 구현)
     */
    public static function removeZeroWidthCharacters(string $string): string {
        // 구현 예정
        return $string;
    }
}

/**
 * JsonEncoder 전용 예외 클래스
 */
class JsonEncoderException extends \Exception {
    protected $errorCode;
    protected $details;
    
    public function __construct(string $message = "", int $code = 0, array $details = [], \Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $code;
        $this->details = $details;
    }
    
    public function getErrorCode(): int {
        return $this->errorCode;
    }
    
    public function getDetails(): array {
        return $this->details;
    }
}