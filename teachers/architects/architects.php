<?php
// 데이터 배열 정의
$allCompanies = [
    [
        "title" => "UNITAS",
        "emoji" => "👑",
        "sections" => [
            [
                "title" => "TCrystal UN",
                "emoji" => "💎",
                "items" => [
                    "TL 생성장치 UN",
                    "비즈니스 모델 UN",
                    "KPI & OKR UN",
                    "재무성장 전략 UN",
                    "SWOT 분석 UN"
                ]
            ],
            [
                "title" => "전쟁 Kanban UN",
                "emoji" => "💥",
                "items" => [
                    "Internal Branding UN",
                    "Vertical Drilling UN",
                    "에이전트 가든 UN",
                    "서비스 파이프라이닝 UN",
                    "External Branding UNI",
                    "마케팅 파이프라인 UN",
                    "WTC UNITAS",
                    "타임라인 실행자 UN",
                    "AI 정원사 양성 UN"
                ]
            ],
            [
                "title" => "신경망 건축가 UN",
                "emoji" => "🌐",
                "items" => [
                    "정보 블랙홀 UN",
                    "제텔카스텐 UN",
                    "KC 생성기 UN",
                    "Soft BackBN  UN",
                    "💊 무지성 휴리스틱"
                ]
            ]
        ]
    ],
    [
        "title" => "정보체 TR",
        "emoji" => "🏛️",
        "sections" => [
            [
                "title" => "타임 크리스탈 TR",
                "emoji" => "💎",
                "items" => [
                    "타임라인 생성장치 TR",
                    "비즈니스 모델 TR",
                    "KPI & OKR TR",
                    "재무성장 전략 TR",
                    "SWOT 분석 TR"
                ]
            ],
            [
                "title" => "전쟁 Kanban TR",
                "emoji" => "💥",
                "items" => [
                    "Internal Branding TR",
                    "Vertical Drilling TR",
                    "에이전트 가든 TR",
                    "서비스 파이프라이닝 TR",
                    "External Branding TR",
                    "트래픽 발생장치 TR",
                    "Win this company TR",
                    "타임라인 실행자 TR",
                    "AI 정원사 양성 TR"
                ]
            ],
            [
                "title" => "신경망 건축가 TR",
                "emoji" => "🌐",
                "items" => [
                    "정보 블랙홀 TR",
                    "제텔카스텐 TR",
                    "지식결정 생성기 TR",
                    "Soft BackBone TR",
                    "⚖️📐📏🧩🕸️🗂️🎯🌟"
                ]
            ]
        ]
    ],
    // 이하 나머지 회사들 동일한 방식으로 추가...
    [
      "title" => "지식체 BGI",
      "emoji" => "🏁",
      "sections" => [
        [
          "title" => "타임 크리스탈 BI",
          "emoji" => "💎",
          "items" => [
            "타임라인 생성장치 BI",
            "비즈니스 모델 BI",
            "KPI & OKR BI",
            "재무성장 전략 BI",
            "SWOT 분석 BI"
          ]
        ],
        [
          "title" => "전쟁 Kanban BI",
          "emoji" => "💥",
          "items" => [
            "Internal Branding BI",
            "Vertical Drilling BI",
            "에이전트 가든 BI",
            "서비스 파이프라이닝 BI",
            "External Branding BI",
            "트래픽 발생장치 BI",
            "Win this company BI",
            "타임라인 실행자 BI",
            "AI 정원사 양성 BI"
          ]
        ],
        [
          "title" => "신경망 건축가 BI",
          "emoji" => "🌐",
          "items" => [
            "정보 블랙홀 BI",
            "제텔카스텐 BI",
            "지식결정 생성기 BI",
            "Soft BackBone BI",
            "🔄⏭️⏮️⏹️⏏️↩️🚦🛑"
          ]
        ]
      ]
    ],
    // ... 나머지 모든 회사 및 섹션, 아이템 동일하게 추가 ...
    [
        "title" => "무지성의 꿈 (C)",
        "emoji" => "🧲",
        "sections" => [
            [
                "title" => "타임 크리스탈 C",
                "emoji" => "💎",
                "items" => [
                    "타임라인 생성장치 C",
                    "비즈니스 모델 C",
                    "KPI & OKR C",
                    "재무성장 전략 C",
                    "SWOT 분석 C"
                ]
            ],
            [
                "title" => "전쟁 Kanban C",
                "emoji" => "💥",
                "items" => [
                    "Internal Branding C",
                    "Vertical Drilling C",
                    "에이전트 가든 C",
                    "서비스 파이프라이닝 C",
                    "External Branding C",
                    "트래픽 발생장치 C",
                    "Win this company C",
                    "타임라인 실행자 C",
                    "AI 정원사 양성 C"
                ]
            ],
            [
                "title" => "신경망 건축가 C",
                "emoji" => "🌐",
                "items" => [
                    "정보 블랙홀 C",
                    "제텔카스텐 C",
                    "지식결정 생성기 C",
                    "Soft BackBone C",
                    "📒🏓📊💰🔒🌦️👥📡🏹"
                ]
            ]
        ]
    ]
];
?>

<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.0.23/dist/tailwind.min.css" rel="stylesheet"/>
<title>Business Intelligence Dashboard</title>
<style>
    .section-content { display: none; }
    .section-expanded .section-content { display: block; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.section-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var parent = btn.closest('.section-container');
            parent.classList.toggle('section-expanded');
        });
    });
});
</script>
</head>
<body class="bg-gray-900 p-6 min-h-screen text-gray-200">
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-100">Business Intelligence Dashboard</h1>
    </div>
    <div class="flex overflow-x-auto pb-4">
        <?php foreach ($allCompanies as $company): ?>
        <div class="flex-none w-80 bg-gray-800 rounded-lg p-3 mr-4">
            <div class="mb-4">
                <h3 class="text-gray-100 font-medium flex items-center gap-2">
                    <?php echo $company['emoji']; ?> <?php echo $company['title']; ?>
                </h3>
            </div>
            <div>
                <?php foreach ($company['sections'] as $section): ?>
                <div class="mb-3 section-container">
                    <button class="w-full flex items-center justify-between p-2 bg-gray-800 hover:bg-gray-700 rounded-md text-gray-200 section-toggle">
                        <div class="flex items-center space-x-2">
                            <!-- 기본 상태에선 우측 화살표(▶), 펼쳐지면 ▼ 표시 -->
                            <span class="icon-closed"><svg width="16" height="16" fill="none" stroke="currentColor"><path d="M4 4l8 4-8 4V4z"/></svg></span>
                            <span class="icon-open hidden"><svg width="16" height="16" fill="none" stroke="currentColor"><path d="M4 12l4-8 4 8H4z"/></svg></span>
                            <span class="text-sm whitespace-nowrap">
                                <?php echo $section['title']; ?> <?php echo $section['emoji']; ?>
                            </span>
                        </div>
                    </button>
                    <div class="pl-6 py-2 space-y-2 section-content">
                        <?php foreach ($section['items'] as $item): ?>
                            <div class="flex items-center text-gray-300 hover:bg-gray-700 rounded p-2">
                                <input type="checkbox" class="mr-2" />
                                <span class="text-sm"><?php echo $item; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
