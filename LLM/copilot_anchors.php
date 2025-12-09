<?php

$answerShort = false; // 짧게 대답할지
$count = 12; // 대화의 횟수 

$rolea='GPT_2';
$roleb='GPT_1';

$temperature=1;

$talka1='설명을 요청합니다.';
$talka2='답변 중에서 궁금한 부분을 추가로 질문합니다.';
$talka3='자신의 이해도에 대한 평가를 요청합니다.';

$talkb1='질문에 쉽게 답변합니다.정확한 내용을 전달합니다.';
$talkb2='교과서 내용과 이전 내용을 토대로 답변합니다. 짧은 문장을 사용합니다.';
$talkb3='이해도를 학생이 알 수 있도록 성찰질문을 제공합니다. 다음 주제 제목을 제시';
$tone1='반말로';

if($pagetype==='welcome')
    {
    $talka1='설명을 요청합니다.';
    $talka2='답변 중에서 궁금한 부분을 추가로 질문합니다.';
    }
elseif($pagetype==='vision')
    {
    $talka1='설명을 요청합니다.';
    $talka2='답변 중에서 궁금한 부분을 추가로 질문합니다.';
    }
elseif($pagetype==='goal')
    {
    $talka1='설명을 요청합니다.';
    $talka2='답변 중에서 궁금한 부분을 추가로 질문합니다.';
    }
elseif($pagetype==='meta')
    {
    $talka1='설명을 요청합니다.';
    $talka2='답변 중에서 궁금한 부분을 추가로 질문합니다.';
    }
elseif($pagetype==='mental')
    {
    $talka1='설명을 요청합니다.';
    $talka2='답변 중에서 궁금한 부분을 추가로 질문합니다.';
    }
    elseif($pagetype==='parental')
    {
    $talka1='설명을 요청합니다.';
    $talka2='답변 중에서 궁금한 부분을 추가로 질문합니다.';
    }
elseif($pagetype==='diagnosis')
    {
    $talka1='설명을 요청합니다.';
    $talka2='답변 중에서 궁금한 부분을 추가로 질문합니다.';
    }
elseif($pagetype==='isolated')
    {
    $talka1='설명을 요청합니다.';
    $talka2='답변 중에서 궁금한 부분을 추가로 질문합니다.';
    }
elseif($pagetype==='microinstruction')
    {
    $talka1='설명을 요청합니다.';
    $talka2='답변 중에서 궁금한 부분을 추가로 질문합니다.';
    }
    elseif($pagetype==='anchoring')
    {
    $talka1='설명을 요청합니다.';
    $talka2='답변 중에서 궁금한 부분을 추가로 질문합니다.';
    }
elseif($pagetype==='mining')
    {
    $talka1='설명을 요청합니다.';
    $talka2='답변 중에서 궁금한 부분을 추가로 질문합니다.';
    }
elseif($pagetype==='consolidation')
    {
    $talka1='설명을 요청합니다.';
    $talka2='답변 중에서 궁금한 부분을 추가로 질문합니다.';
    }
?>