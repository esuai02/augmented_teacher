<!DOCTYPE html>
<html>
<head>
  <title>Bootstrap Example</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.1/dist/jquery.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
 
  <style>
    /* Left sidebar */
    .left-sidebar {
      width: 15%;
      height: 100%;
      position: fixed;
      left: 0;
      top: 0;
      background-color: #f1f1f1;
      padding: 20px;
    }
@media only screen and (max-width: 600px) {
  /* styles for widths up to 600px */
  body {
    width: 100%;
  }
  .left-region {
    width: 100%;
    float: none;
  }
}
    /* Main body */
    .main-body {
      width: 15%;
      height: 100%;
    } 

    /* Collapsible button */
    .collapsible {
      background-color: #eee;
      color: #444;
      cursor: pointer;
      padding: 18px;
      width: 100%;
      border: none;
      text-align: left;
      outline: none;
      font-size: 15px;
    }

    /* Add a background color to the button if it is clicked on (add the .active class with JS), and when you move the mouse over it (hover) */
    .active, .collapsible:hover {
      background-color: #ccc;
    }

    /* Style the collapsible content */
    .content {
      padding: 0 18px;
      display: none;
      overflow: hidden;
      background-color: #f1f1f1;
    }
  </style>
</head>
<body>

  <div class="left-sidebar">
    <h3>고등수학 상</h3><br>
    <h6>1. 다항식의 연산</h6>
    <h6>2. 나머지정리와 인수분해</h6>   
    <h6>3. 복소수</h6>
    <h6>4. 이차방정식</h6>    
    <h6>5. 이차방정식과 이차함수</h6>
    <h6>6. 고차방정식</h6>   
    <h6>7. 연립방정식</h6>
    <h6>8. 부등식</h6>   
    <h6>9. 이차부등식</h6>
    <h6>10. 평면좌표</h6>   
    <h6>11. 직선의 방정식</h6>
    <h6>12. 원의 방정식</h6>     
    <h6>13. 도형의 이동</h6>       
  </div>
 

 
<br>
<div class="container">
  <h2>다항식의 연산</h2>
    <div id="accordion">
		
<br>

    <div class="card">
      <div class="card-header">
        <a class="collapsed card-link" data-toggle="collapse" href="#collapse0">
        개요
      </a>
      </div>
      <div id="collapse0" class="collapse show" data-parent="#accordion">
        <div class="card-body">
           다항식의 덧셈, 뺄셈, 곱셈뿐만 아니라 이러한 연산을 수행하는 데 사용되는 다양한 기술과 공식에 대해 배우는 것은 다항식의 동작과 조작 방법을 이해하는 데 도움이 되기 때문에 중요합니다. 이러한 이해는 다항식의 근 또는 영점 찾기, 식 단순화 및 방정식 풀기와 같은 다양한 수학적 문제를 해결하는 데 중요합니다.

분배 속성, FOILing 및 합성 나눗셈과 같은 곱셈 공식은 이러한 작업을 빠르고 효율적으로 수행하는 데 유용한 도구입니다. 이러한 공식은 또한 다항식의 기본 구조와 다른 수학적 개념과의 관계를 이해하는 데 도움이 될 수 있습니다.

다항식의 나눗셈은 하나의 다항식을 다른 것으로 나누는 방법과 몫과 나머지를 찾는 방법을 이해할 수 있게 해주기 때문에 배우는 것도 중요합니다. 이는 식을 단순화하고, 방정식을 풀고, 부분 분수와 같은 개념을 이해할 때 중요합니다.

전반적으로 이러한 작업과 기술에 대해 배우면 다항식과 관련된 문제를 조작하고 해결하는 데 더 능숙해질 수 있으며, 이는 수학 및 기타 분야의 많은 영역에서 유용합니다.
        </div>
      </div>
    </div>  
    <div class="card">
      <div class="card-header">
        <a class="card-link" data-toggle="collapse" href="#collapse1">
         <b>다항식의 덧셈과 뺼셈</b>  다항식에 대한 용어  &nbsp; <a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php">[노트]</a>
        </a>
      </div>
      <div id="collapse1" class="collapse" data-parent="#accordion">
        <div class="card-body">
          다항식 용어를 배우는 것은 다항식의 개념과 속성을 효과적으로 이해하고 전달할 수 있기 때문에 중요합니다. 다항식을 설명하는 데 사용되는 언어와 어휘를 이해하면 수학 텍스트를 읽고 이해할 수 있을 뿐만 아니라 자신의 생각을 명확하고 정확하게 표현할 수 있습니다.

예를 들어 "차수", "계수", "선행 계수", "상수항", "근" 및 "영"이라는 용어를 이해하면 다항식을 분류하는 방법, 다항식의 차수를 찾는 방법을 이해하는 데 도움이 됩니다. , 다항식의 동작을 결정하는 방법.

또한 다항식 용어 학습은 대수학, 미적분학 및 기타 수학 분야의 문제를 해결하는 데 필수적입니다. 또한 물리학, 공학 및 컴퓨터 과학과 같은 응용 분야에서 사용되는 개념과 방법을 이해할 수 있습니다.

요컨대, 다항식 용어를 배우면 다항식을 이해하고 조작하는 능력이 향상되고 수학 및 관련 분야에서 추가 연구를 위한 토대를 제공할 것입니다.
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <a class="collapsed card-link" data-toggle="collapse" href="#collapse2">
       <b>다항식의 덧셈과 뺼셈</b>   다항식의 정리방법
      </a>
      </div>
      <div id="collapse2" class="collapse" data-parent="#accordion">
        <div class="card-body">
          Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <a class="collapsed card-link" data-toggle="collapse" href="#collapse3">
          <b>다항식의 덧셈과 뺼셈</b> 다항식의 덧셈과 뺄셈
        </a>
      </div>
      <div id="collapse3" class="collapse" data-parent="#accordion">
        <div class="card-body">
          Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
        </div>
      </div>
    <div class="card">
      <div class="card-header">
        <a class="collapsed card-link" data-toggle="collapse" href="#collapse4">
           <b>다항식의 덧셈과 뺼셈</b> 다항식의 덧셈에 대한 연산법칙
        </a>
      </div>
      <div id="collapse4" class="collapse" data-parent="#accordion">
        <div class="card-body">
          Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
        </div>
      </div>
    </div>
    
    <br>
    
    <div class="card">
      <div class="card-header">
        <a class="collapsed card-link" data-toggle="collapse" href="#collapse5">
           <b>다항식의 곱셈</b> 지수법칙
        </a>
      </div>
      <div id="collapse5" class="collapse" data-parent="#accordion">
        <div class="card-body">
          Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <a class="card-link" data-toggle="collapse" href="#collapse6">
           <b>다항식의 곱셈</b> 식의 전개
        </a>
      </div>
      <div id="collapse6" class="collapse" data-parent="#accordion">
        <div class="card-body">
          Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
        </div>
      </div>
    </div><br>
    <div class="card">
      <div class="card-header">
        <a class="collapsed card-link" data-toggle="collapse" href="#collapse7">
       <b>곱셈 공식</b>  다항식의 곱셈에 대한 연산법칙
      </a>
      </div>
      <div id="collapse7" class="collapse" data-parent="#accordion">
        <div class="card-body">
          Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <a class="collapsed card-link" data-toggle="collapse" href="#collapse8">
           <b>곱셈 공식</b> 곱셈 공식
        </a>
      </div>
      <div id="collapse8" class="collapse" data-parent="#accordion">
        <div class="card-body">
          Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
        </div>
      </div>
    <div class="card">
      <div class="card-header">
        <a class="collapsed card-link" data-toggle="collapse" href="#collapse9">
            <b>곱셈 공식</b>  곱셈 공식의 변형
        </a>
      </div>
      <div id="collapse9" class="collapse" data-parent="#accordion">
        <div class="card-body">
          Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
        </div>
      </div>
    </div>
    
    <br>
    
    
    <div class="card">
      <div class="card-header">
        <a class="collapsed card-link" data-toggle="collapse" href="#collapse10">
          <b>다항식의 나눗셈</b> (다항식)÷(단항식)의 계산
        </a>
      </div>
      <div id="collapse10" class="collapse" data-parent="#accordion">
        <div class="card-body">
          Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <a class="card-link" data-toggle="collapse" href="#collapse11">
          <b>다항식의 나눗셈</b> (다항식)÷(다항식)의 계산
        </a>
      </div>
      <div id="collapse11" class="collapse" data-parent="#accordion">
        <div class="card-body">
          Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <a class="collapsed card-link" data-toggle="collapse" href="#collapse12">
         <b>다항식의 나눗셈</b> 다항식의 나눗셈에 대한 등식
      </a>
      </div>
      <div id="collapse12" class="collapse" data-parent="#accordion">
        <div class="card-body">
          Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <a class="collapsed card-link" data-toggle="collapse" href="#collapse13">
         <b>다항식의 나눗셈</b> 조립제법
        </a>
      </div>
      <div id="collapse13" class="collapse" data-parent="#accordion">
        <div class="card-body">
          Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
        </div>
      </div>
    <div class="card">
      <div class="card-header">
        <a class="collapsed card-link" data-toggle="collapse" href="#collapse14">
          <b>다항식의 나눗셈</b> 조립제법의 확장
        </a>
      </div>
      <div id="collapse14" class="collapse" data-parent="#accordion">
        <div class="card-body">
          Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
        </div>
      </div>
    </div>
    
    <br>
    
    <div class="card">
      <div class="card-header">
        <a class="collapsed card-link" data-toggle="collapse" href="#collapse15">
          단원 마무리 활동
        </a>
      </div>
      <div id="collapse15" class="collapse" data-parent="#accordion">
        <div class="card-body">
          Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
        </div>
      </div>
    </div>
   
  </div>
</div>
     

</body>
</html>
