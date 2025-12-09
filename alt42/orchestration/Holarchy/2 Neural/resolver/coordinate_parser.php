<?php
/**
 * 홀론 좌표 파서
 * 좌표 문법: {holon_id}.{section}.{subsection}[{index}].{field}
 * 
 * 예시:
 *   meeting-2025-007.W.goal.ultimate
 *   meeting-2025-007.X.issues[0].description
 *   05-api-system.E.execution_plan[2].eta
 * 
 * 파일: alt42/orchestration/Holarchy/2 Neural/resolver/coordinate_parser.php
 */

class CoordinateParser {
    
    /**
     * 좌표 문자열을 파싱하여 구조화된 배열로 반환
     * 
     * @param string $coordinate 좌표 문자열
     * @return array 파싱된 좌표 정보
     */
    public static function parse($coordinate) {
        $result = [
            'holon_id' => null,
            'section' => null,
            'path' => [],
            'raw' => $coordinate,
            'valid' => false
        ];
        
        if (empty($coordinate)) {
            return $result;
        }
        
        // 첫 번째 점(.) 기준으로 holon_id 분리
        $firstDot = strpos($coordinate, '.');
        if ($firstDot === false) {
            $result['holon_id'] = $coordinate;
            $result['valid'] = true;
            return $result;
        }
        
        $result['holon_id'] = substr($coordinate, 0, $firstDot);
        $remaining = substr($coordinate, $firstDot + 1);
        
        // WXSPERTA 섹션 추출
        if (preg_match('/^([WXSPERTA])(?:\.(.*))?$/', $remaining, $match)) {
            $result['section'] = $match[1];
            
            if (isset($match[2]) && $match[2] !== '') {
                $result['path'] = self::parsePath($match[2]);
            }
            
            $result['valid'] = true;
        } else {
            // 섹션이 아닌 경우 전체를 경로로 처리
            $result['path'] = self::parsePath($remaining);
            $result['valid'] = true;
        }
        
        return $result;
    }
    
    /**
     * 경로 문자열을 파싱
     * 예: "issues[0].description" → [["key" => "issues"], ["index" => 0], ["key" => "description"]]
     */
    private static function parsePath($pathStr) {
        $path = [];
        $parts = preg_split('/\.(?![^\[]*\])/', $pathStr); // 배열 내부의 .은 무시
        
        foreach ($parts as $part) {
            if (empty($part)) continue;
            
            // 배열 인덱스 체크: key[0] or [0]
            if (preg_match('/^(\w*)\[(\d+)\]$/', $part, $match)) {
                if ($match[1] !== '') {
                    $path[] = ['key' => $match[1]];
                }
                $path[] = ['index' => (int)$match[2]];
            } else {
                $path[] = ['key' => $part];
            }
        }
        
        return $path;
    }
    
    /**
     * JSON 데이터에서 경로에 해당하는 값 추출
     * 
     * @param array $data JSON 데이터
     * @param array $parsedCoord 파싱된 좌표
     * @return mixed 추출된 값
     */
    public static function resolve($data, $parsedCoord) {
        if (!$parsedCoord['valid']) {
            return null;
        }
        
        $current = $data;
        
        // 섹션으로 이동
        if ($parsedCoord['section']) {
            if (!isset($current[$parsedCoord['section']])) {
                return null;
            }
            $current = $current[$parsedCoord['section']];
        }
        
        // 경로 순회
        foreach ($parsedCoord['path'] as $step) {
            if (isset($step['key'])) {
                if (!is_array($current) || !isset($current[$step['key']])) {
                    return null;
                }
                $current = $current[$step['key']];
            } elseif (isset($step['index'])) {
                if (!is_array($current) || !isset($current[$step['index']])) {
                    return null;
                }
                $current = $current[$step['index']];
            }
        }
        
        return $current;
    }
    
    /**
     * 좌표를 문자열로 재구성
     */
    public static function stringify($parsedCoord) {
        $parts = [$parsedCoord['holon_id']];
        
        if ($parsedCoord['section']) {
            $parts[] = $parsedCoord['section'];
        }
        
        $pathStr = '';
        foreach ($parsedCoord['path'] as $step) {
            if (isset($step['key'])) {
                $pathStr .= ($pathStr ? '.' : '') . $step['key'];
            } elseif (isset($step['index'])) {
                $pathStr .= '[' . $step['index'] . ']';
            }
        }
        
        if ($pathStr) {
            $parts[] = $pathStr;
        }
        
        return implode('.', $parts);
    }
    
    /**
     * 좌표 유효성 검사
     */
    public static function validate($coordinate) {
        $parsed = self::parse($coordinate);
        
        $errors = [];
        
        if (empty($parsed['holon_id'])) {
            $errors[] = 'holon_id가 필요합니다';
        }
        
        if ($parsed['section'] && !in_array($parsed['section'], ['W', 'X', 'S', 'P', 'E', 'R', 'T', 'A'])) {
            $errors[] = '유효하지 않은 섹션입니다. W, X, S, P, E, R, T, A 중 하나여야 합니다';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'parsed' => $parsed
        ];
    }
}

// 테스트 모드
if (php_sapi_name() === 'cli' || (isset($_GET['test']) && $_GET['test'] === '1')) {
    $testCases = [
        'meeting-2025-007',
        'meeting-2025-007.W',
        'meeting-2025-007.W.goal.ultimate',
        'meeting-2025-007.X.issues[0]',
        'meeting-2025-007.X.issues[0].description',
        '05-api-system.E.execution_plan[2].eta',
    ];
    
    echo "=== 좌표 파서 테스트 ===\n\n";
    
    foreach ($testCases as $coord) {
        $parsed = CoordinateParser::parse($coord);
        echo "Input: $coord\n";
        echo "Parsed: " . json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        echo "Stringify: " . CoordinateParser::stringify($parsed) . "\n";
        echo "---\n";
    }
}

