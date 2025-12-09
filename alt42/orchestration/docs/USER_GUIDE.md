# Agent Problem Targeting System - User Guide

## 📋 Overview

The Agent Problem Targeting System allows you to identify and analyze specific problems handled by each of the 21 AI agents in the ALT42 system. Each agent specializes in a particular aspect of student learning, and this system helps you target interventions more precisely.

**Version**: 1.0
**Last Updated**: 2025-01-21
**Author**: ALT42 Development Team

---

## 🎯 What is Agent Problem Targeting?

Each of the 21 agents in ALT42 monitors specific aspects of student learning and can identify common problems in their domain. The Problem Targeting System:

1. **Displays Agent-Specific Problems**: Shows 5-7 typical problems each agent can address
2. **Generates AI-Powered Analysis**: Uses GPT-4 to create customized analysis reports
3. **Provides Actionable Insights**: Offers structured recommendations with expected outcomes

---

## 🚀 Getting Started

### Accessing the System

1. Navigate to: `https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/`
2. Log in with your Moodle credentials
3. You'll see the main dashboard with 21 agent cards

### Understanding the Interface

**Main Dashboard Layout**:
- **Left Sidebar**: 21 agent cards displayed vertically
- **Right Panel**: Analysis reports and detailed information
- **Agent Cards**: Each card shows:
  - Agent number (01-21)
  - Agent name in Korean
  - Agent icon
  - **🎯 문제 타게팅** button at the bottom

---

## 📖 Step-by-Step Usage

### Step 1: Select an Agent

1. Review the 21 agent cards in the left sidebar
2. Each agent has a specific focus area (see Agent List section below)
3. Click the **🎯 문제 타게팅** button on any agent card

**Example**: Click on Agent 01 (실시간 온보딩) to see onboarding-related problems

### Step 2: Review Problem List

A popup window will appear showing:
- **Agent Header**: Agent number, name, icon, and description
- **Info Box**: Explanation of what the problem list represents
- **Problem List**: 5-7 clickable problem items
- **Close Button**: ✕ in the top-right corner

**Keyboard Navigation**:
- Press `Tab` to navigate through problem items
- Press `Enter` or `Space` to select a problem
- Press `Escape` to close the popup

### Step 3: Select a Problem

1. Review the list of problems
2. Click on the problem that matches your situation
3. The popup will close and analysis will begin

**Example Problems** (Agent 01):
- "학생 정보가 불완전하여 정확한 진단이 어렵다"
- "초기 학습 상태 파악에 시간이 오래 걸린다"
- "학생의 MBTI 학습 성향 데이터가 누락되어 있다"

### Step 4: Review Analysis Report

The right panel will open with:
- **Loading State**: Animated agent icon while analysis generates (5-20 seconds)
- **Analysis Report**: Four structured sections
  - 📋 **문제 상황**: Current situation description
  - 🔍 **원인 분석**: Root cause analysis
  - 💡 **개선 방안**: Step-by-step improvement plan
  - 📊 **예상 효과**: Expected outcomes with metrics

**Analysis Generation Time**:
- **With GPT-4**: 10-20 seconds (higher quality)
- **Placeholder Mode**: 1-2 seconds (when GPT not configured)

### Step 5: Use the Insights

1. Read through all four sections of the analysis
2. Note the specific improvement steps
3. Review expected outcomes and metrics
4. Close the panel when done (✕ button)

---

## 👥 Complete Agent List

| Number | Name | Focus Area |
|--------|------|------------|
| 01 | 실시간 온보딩 | Student profile and initial onboarding |
| 02 | 시험 일정 관리 | Exam schedule and D-day urgency |
| 03 | 목표 분석 | Goal achievement and strategy optimization |
| 04 | 문제 활동 최적화 | Problem-solving activity optimization |
| 05 | 학습 감정 관리 | Learning emotion and motivation |
| 06 | 교사 피드백 반영 | Teacher feedback integration |
| 07 | 상호작용 타게팅 | Interaction targeting selection |
| 08 | 침착도 관리 | Calmness and focus management |
| 09 | 학습관리 종합 | Comprehensive learning management |
| 10 | 개념노트 분석 | Concept note analysis |
| 11 | 오답노트 분석 | Error note pattern analysis |
| 12 | 휴식 루틴 최적화 | Rest routine optimization |
| 13 | 학습 이탈 방지 | Learning dropout prevention |
| 14 | 현재 위치 파악 | Current position tracking |
| 15 | 문제 재정의 | Problem redefinition |
| 16 | 상호작용 준비 | Interaction preparation |
| 17 | 잔여 활동 조정 | Remaining activity adjustment |
| 18 | 시그너처 루틴 | Signature routine optimization |
| 19 | 상호작용 컨텐츠 | Interaction content generation |
| 20 | 개입 준비 | Intervention preparation |
| 21 | 개입 실행 | Intervention execution |

---

## ⚠️ Troubleshooting

### Problem Popup Not Opening

**Symptoms**: Clicking 🎯 button does nothing

**Solutions**:
1. Check browser console for JavaScript errors (F12)
2. Refresh the page (Ctrl+F5 or Cmd+Shift+R)
3. Clear browser cache and reload
4. Ensure JavaScript is enabled in browser settings

### Analysis Takes Too Long

**Symptoms**: Loading spinner runs for >60 seconds

**What Happens**:
- System automatically times out after 60 seconds
- You'll see a timeout warning with retry button
- You can retry up to 3 times

**Solutions**:
1. Click the **🔄 재시도** button to retry
2. If all retries fail, try:
   - Selecting a different problem
   - Refreshing the page
   - Checking GPT API status (for administrators)

### Analysis Panel Won't Close

**Symptoms**: ✕ button not responding

**Solutions**:
1. Try clicking outside the panel
2. Press `Escape` key
3. Refresh the page
4. Check browser console for errors

### GPT Analysis Not Working

**Symptoms**: Only seeing placeholder analysis, not AI-generated content

**For Administrators**:
1. Check `/api/gpt_config.php` for valid API key
2. Verify OpenAI API key is not expired
3. Check server error logs: `/var/log/apache2/error.log`
4. See `GPT_SETUP.md` for configuration guide

---

## ♿ Accessibility Features

The system is designed for accessibility:

### Keyboard Navigation
- **Tab**: Navigate between elements
- **Enter/Space**: Activate buttons and select problems
- **Escape**: Close popups and panels
- **Focus Indicators**: Visible outline on focused elements

### Screen Reader Support
- **ARIA Labels**: All interactive elements labeled
- **Role Attributes**: Proper semantic roles (dialog, button)
- **Live Regions**: Status updates announced to screen readers

### Visual Accessibility
- **High Contrast Mode**: Automatic adaptation for high contrast settings
- **Reduced Motion**: Respects `prefers-reduced-motion` user preference
- **Color Contrast**: WCAG 2.1 AA compliant color ratios
- **Font Sizes**: Large, readable text throughout

### Mobile Support
- **Responsive Design**: Adapts to mobile screens
- **Touch Targets**: Minimum 44×44px touch areas
- **Mobile Optimization**: Optimized layouts for small screens

---

## 💡 Best Practices

### For Teachers

1. **Review Multiple Agents**: Different agents may identify related problems
2. **Track Patterns**: Note recurring problems across students
3. **Use Analysis Insights**: Apply improvement plans systematically
4. **Monitor Outcomes**: Track whether expected effects materialize

### For Administrators

1. **Configure GPT API**: Set up OpenAI API key for best results (see `GPT_SETUP.md`)
2. **Monitor Performance**: Track analysis generation times
3. **Review Logs**: Check error logs periodically
4. **Update Regularly**: Keep agent problem definitions current

### For Students

1. **Be Specific**: Select problems that most closely match your situation
2. **Read Thoroughly**: Review all four sections of analysis
3. **Take Action**: Follow the improvement plan steps
4. **Track Progress**: Note improvements over time

---

## 📊 Understanding Analysis Reports

### Section 1: 문제 상황 (Problem Situation)
- **Purpose**: Describes current state
- **Length**: 2-3 sentences
- **Content**: Specific details about what's happening
- **Use**: Confirm the analysis understands your situation

### Section 2: 원인 분석 (Root Cause Analysis)
- **Purpose**: Identifies underlying causes
- **Length**: 3-5 causes listed
- **Content**: Educational theory and practical factors
- **Use**: Understand why the problem exists

### Section 3: 개선 방안 (Improvement Plan)
- **Purpose**: Provides actionable steps
- **Length**: 3-5 step plan
- **Content**: Specific, executable actions with timeline
- **Use**: Follow these steps to improve

### Section 4: 예상 효과 (Expected Outcomes)
- **Purpose**: Predicts results of improvement
- **Length**: 2-3 outcomes with metrics
- **Content**: Quantitative improvements and timeframes
- **Use**: Set expectations and measure progress

---

## 🔄 System Updates

### Version History

**v1.0 (2025-01-21)**:
- Initial release with 21 agents
- 126 total problems across all agents
- GPT-4 API integration
- Accessibility features
- Timeout handling and retry mechanism
- Responsive design

### Planned Features

**v1.1 (Future)**:
- Analysis history tracking
- Problem frequency statistics
- Multi-student comparison
- Export analysis reports to PDF
- Teacher annotations and notes

---

## 📞 Support

For technical support or questions:

1. **Check Documentation**: Review this guide and `DEVELOPER.md`
2. **Check Logs**: Server error logs at `/var/log/apache2/error.log`
3. **Report Issues**: Contact system administrator
4. **Feature Requests**: Submit through Moodle support system

---

## 📚 Related Documentation

- **Developer Guide**: `/docs/DEVELOPER.md` - Technical implementation details
- **GPT Setup**: `/api/GPT_SETUP.md` - OpenAI API configuration
- **Reference**: `/docs/reference-popup-mechanism.md` - Popup system architecture
- **Implementation Plan**: `/docs/plans/2025-01-21-agent-interaction-targeting-popup.md`

---

**Need Help?** Contact your system administrator or refer to the developer documentation for technical details.
