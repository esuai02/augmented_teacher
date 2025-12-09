<?php
// MathKing LMS DB 연결 설정
$host = '58.180.27.46';
$dbname = 'mathking';
$username = 'moodle';
$password = '@MCtrigd7128';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("DB 연결 실패: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>학생 검색 시스템</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .search-box {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .search-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-btn {
            padding: 12px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .clear-btn {
            padding: 12px 30px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .clear-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .results-box {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .results-title {
            font-size: 1.5em;
            color: #333;
            font-weight: 600;
        }

        .results-count {
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
        }

        .student-table {
            width: 100%;
            border-collapse: collapse;
        }

        .student-table thead {
            background: #f8f9fa;
        }

        .student-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #dee2e6;
        }

        .student-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }

        .student-table tbody tr {
            transition: background 0.3s;
        }

        .student-table tbody tr:hover {
            background: #f8f9fa;
        }

        .student-name {
            font-weight: 600;
            color: #333;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .badge-grade {
            background: #e3f2fd;
            color: #1976d2;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .no-results-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }

        .filter-tags {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: #f0f0f0;
            border-radius: 20px;
            font-size: 0.9em;
        }

        .filter-tag.active {
            background: #667eea;
            color: white;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2em;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-btn, .clear-btn {
                width: 100%;
            }

            .student-table {
                font-size: 0.9em;
            }

            .student-table th,
            .student-table td {
                padding: 10px;
            }
        }

        .loading {
            text-align: center;
            padding: 40px;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 학생 검색 시스템</h1>
        </div>

        <div class="search-box">
            <form class="search-form" method="GET" action="">
                <div class="form-group">
                    <label for="searchType">검색 유형</label>
                    <select class="form-control" id="searchType" name="search_type">
                        <option value="all" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'all') ? 'selected' : ''; ?>>전체 검색</option>
                        <option value="name" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'name') ? 'selected' : ''; ?>>이름</option>
                        <option value="institution" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'institution') ? 'selected' : ''; ?>>기관</option>
                        <option value="email" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'email') ? 'selected' : ''; ?>>이메일</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="searchQuery">검색어</label>
                    <input type="text" class="form-control" id="searchQuery" name="search_query" 
                           placeholder="검색어를 입력하세요" 
                           value="<?php echo isset($_GET['search_query']) ? htmlspecialchars($_GET['search_query']) : ''; ?>">
                </div>
                <button type="submit" class="search-btn">검색</button>
                <button type="button" class="clear-btn" onclick="clearSearch()">초기화</button>
            </form>
        </div>

        <div class="results-box">
            <div class="results-header">
                <h2 class="results-title">검색 결과</h2>
                <span class="results-count" id="resultsCount">
                    전체: 0명
                </span>
            </div>

            <?php if (isset($_GET['search_query']) && !empty($_GET['search_query'])): ?>
                <div class="filter-tags">
                    <span class="filter-tag active">
                        <?php 
                        $type_labels = [
                            'all' => '전체',
                            'name' => '이름',
                            'institution' => '기관',
                            'email' => '이메일'
                        ];
                        echo $type_labels[$_GET['search_type'] ?? 'all'] . ': ' . htmlspecialchars($_GET['search_query']);
                        ?>
                    </span>
                </div>
            <?php endif; ?>

            <div id="resultsArea">
                <?php
                // MathKing LMS에서 실제 데이터 가져오기
                $results = [];
                $search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';
                $search_type = isset($_GET['search_type']) ? $_GET['search_type'] : 'all';
                $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                $per_page = 50; // 페이지당 표시할 학생 수
                $offset = ($page - 1) * $per_page;
                
                try {
                    // 전체 건수 조회 쿼리
                    $count_query = "
                        SELECT COUNT(*) as total
                        FROM mdl_user u
                        WHERE u.deleted = 0 
                          AND u.confirmed = 1
                          AND u.suspended = 0
                          AND u.id > 1
                    ";
                    
                    // 데이터 조회 쿼리
                    $base_query = "
                        SELECT 
                            u.id,
                            u.username,
                            u.firstname,
                            u.lastname,
                            u.email,
                            u.institution,
                            u.department,
                            u.city,
                            u.country,
                            FROM_UNIXTIME(u.lastaccess) as last_access,
                            FROM_UNIXTIME(u.timecreated) as created_date
                        FROM mdl_user u
                        WHERE u.deleted = 0 
                          AND u.confirmed = 1
                          AND u.suspended = 0
                          AND u.id > 1
                    ";
                    
                    // 검색 조건 추가
                    if (!empty($search_query)) {
                        $search_condition = "";
                        switch($search_type) {
                            case 'name':
                                $search_condition = " AND (LOWER(CONCAT(u.firstname, ' ', u.lastname)) LIKE :query 
                                                OR LOWER(u.firstname) LIKE :query 
                                                OR LOWER(u.lastname) LIKE :query)";
                                break;
                            case 'institution':
                                $search_condition = " AND LOWER(u.institution) LIKE :query";
                                break;
                            case 'email':
                                $search_condition = " AND LOWER(u.email) LIKE :query";
                                break;
                            case 'all':
                            default:
                                $search_condition = " AND (
                                    LOWER(CONCAT(u.firstname, ' ', u.lastname)) LIKE :query 
                                    OR LOWER(u.firstname) LIKE :query 
                                    OR LOWER(u.lastname) LIKE :query
                                    OR LOWER(u.email) LIKE :query
                                    OR LOWER(u.institution) LIKE :query
                                    OR LOWER(u.username) LIKE :query
                                )";
                                break;
                        }
                        $count_query .= $search_condition;
                        $base_query .= $search_condition;
                    }
                    
                    // 전체 건수 조회
                    $count_stmt = $pdo->prepare($count_query);
                    if (!empty($search_query)) {
                        $count_stmt->bindValue(':query', '%' . strtolower($search_query) . '%', PDO::PARAM_STR);
                    }
                    $count_stmt->execute();
                    $total_count = $count_stmt->fetch()['total'];
                    $total_pages = ceil($total_count / $per_page);
                    
                    // 데이터 조회 (페이지네이션 적용)
                    $base_query .= " ORDER BY u.lastaccess DESC LIMIT :limit OFFSET :offset";
                    
                    $stmt = $pdo->prepare($base_query);
                    if (!empty($search_query)) {
                        $stmt->bindValue(':query', '%' . strtolower($search_query) . '%', PDO::PARAM_STR);
                    }
                    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
                    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                    
                    $stmt->execute();
                    $results = $stmt->fetchAll();
                    
                } catch(PDOException $e) {
                    echo '<div class="no-results"><p>데이터베이스 오류: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
                    $results = [];
                    $total_count = 0;
                    $total_pages = 0;
                }
                
                $total_results = count($results);
                ?>

                <?php if ($total_count > 0): ?>
                    <!-- 전체 결과 정보 -->
                    <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <p style="margin: 0; color: #666;">
                            전체 <strong style="color: #667eea;"><?php echo number_format($total_count); ?>명</strong>의 학생 중 
                            <?php echo number_format($offset + 1); ?>-<?php echo number_format(min($offset + $per_page, $total_count)); ?>번째 학생을 표시하고 있습니다.
                        </p>
                    </div>
                    
                    <table class="student-table">
                        <thead>
                            <tr>
                                <th>번호</th>
                                <th>사용자명</th>
                                <th>이름</th>
                                <th>이메일</th>
                                <th>기관</th>
                                <th>마지막 접속</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $index => $student): ?>
                            <tr>
                                <td><?php echo $offset + $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($student['username']); ?></td>
                                <td class="student-name"><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['institution'] ?: '-'); ?></td>
                                <td><?php echo $student['last_access'] ? date('Y-m-d H:i', strtotime($student['last_access'])) : '접속 기록 없음'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- 페이지네이션 -->
                    <?php if ($total_pages > 1): ?>
                    <div style="margin-top: 30px; text-align: center;">
                        <style>
                            .pagination {
                                display: inline-flex;
                                gap: 5px;
                                align-items: center;
                            }
                            .pagination a, .pagination span {
                                padding: 8px 12px;
                                text-decoration: none;
                                border: 1px solid #dee2e6;
                                color: #667eea;
                                border-radius: 5px;
                                transition: all 0.3s;
                            }
                            .pagination a:hover {
                                background: #667eea;
                                color: white;
                            }
                            .pagination .current {
                                background: #667eea;
                                color: white;
                                border-color: #667eea;
                            }
                            .pagination .disabled {
                                color: #999;
                                cursor: not-allowed;
                            }
                        </style>
                        <div class="pagination">
                            <?php
                            $query_params = $_GET;
                            
                            // 이전 페이지
                            if ($page > 1):
                                $query_params['page'] = $page - 1;
                            ?>
                                <a href="?<?php echo http_build_query($query_params); ?>">« 이전</a>
                            <?php else: ?>
                                <span class="disabled">« 이전</span>
                            <?php endif; ?>
                            
                            <?php
                            // 페이지 번호 표시 (최대 10개)
                            $start_page = max(1, $page - 5);
                            $end_page = min($total_pages, $start_page + 9);
                            
                            if ($start_page > 1):
                                $query_params['page'] = 1;
                            ?>
                                <a href="?<?php echo http_build_query($query_params); ?>">1</a>
                                <?php if ($start_page > 2): ?>
                                    <span>...</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: 
                                    $query_params['page'] = $i;
                                ?>
                                    <a href="?<?php echo http_build_query($query_params); ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($end_page < $total_pages):
                                if ($end_page < $total_pages - 1): ?>
                                    <span>...</span>
                                <?php endif;
                                $query_params['page'] = $total_pages;
                            ?>
                                <a href="?<?php echo http_build_query($query_params); ?>"><?php echo $total_pages; ?></a>
                            <?php endif; ?>
                            
                            <?php
                            // 다음 페이지
                            if ($page < $total_pages):
                                $query_params['page'] = $page + 1;
                            ?>
                                <a href="?<?php echo http_build_query($query_params); ?>">다음 »</a>
                            <?php else: ?>
                                <span class="disabled">다음 »</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="no-results">
                        <div class="no-results-icon">🔍</div>
                        <p>검색 결과가 없습니다</p>
                        <p style="margin-top: 10px; font-size: 0.9em;">다른 검색어로 시도해보세요</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // 검색 초기화
        function clearSearch() {
            document.getElementById('searchQuery').value = '';
            document.getElementById('searchType').value = 'all';
            window.location.href = window.location.pathname;
        }

        // 검색어 입력 시 엔터키 처리
        document.getElementById('searchQuery').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('.search-form').submit();
            }
        });

        // 결과 수 업데이트
        document.addEventListener('DOMContentLoaded', function() {
            <?php if(isset($total_count)): ?>
                document.getElementById('resultsCount').innerHTML = '전체: <strong><?php echo number_format($total_count); ?>명</strong>' + 
                    <?php if($total_count > 0): ?>
                    ' (페이지 <?php echo $page; ?>/<?php echo $total_pages; ?>)'
                    <?php else: ?>
                    ''
                    <?php endif; ?>;
            <?php endif; ?>
        });
    </script>
</body>
</html>