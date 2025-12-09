/**
 * Experiment API JavaScript Functions
 * 실험 관리 API 호출 함수들
 */

// 실험 저장
async function saveExperiment(experimentData) {
    try {
        console.log('실험 저장 API 호출 시도:', experimentData);
        
        const requestData = {
            action: 'save_experiment',
            experiment_id: experimentData.id || '',
            experiment_name: experimentData.name || '',
            description: experimentData.description || '',
            start_date: experimentData.startDate || '',
            duration_weeks: experimentData.durationWeeks || 8,
            status: experimentData.status || 'planned',
            created_by: experimentData.createdBy || 0
        };
        
        console.log('요청 파라미터:', requestData);
        
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(requestData)
        });
        
        console.log('응답 상태:', response.status);
        
        const responseText = await response.text();
        console.log('응답 텍스트:', responseText);
        
        const result = JSON.parse(responseText);
        console.log('파싱된 결과:', result);
        
        if (result.success) {
            console.log('실험 저장 성공:', result.message);
            return result;
        } else {
            console.error('실험 저장 실패:', result.error);
            throw new Error(result.error);
        }
        
    } catch (error) {
        console.error('실험 저장 실패:', error);
        throw error;
    }
}

// 실험 조회
async function getExperiment(experimentId) {
    try {
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_experiment',
                experiment_id: experimentId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            return result.experiment;
        } else {
            throw new Error(result.error);
        }
        
    } catch (error) {
        console.error('실험 조회 실패:', error);
        throw error;
    }
}

// 실험 목록 조회
async function getExperimentsList(options = {}) {
    try {
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_experiments_list',
                created_by: options.createdBy || '',
                status: options.status || '',
                limit: options.limit || 50,
                offset: options.offset || 0
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            return result.experiments;
        } else {
            throw new Error(result.error);
        }
        
    } catch (error) {
        console.error('실험 목록 조회 실패:', error);
        throw error;
    }
}

// 개입 방법 저장
async function saveInterventionMethod(experimentId, methodData) {
    try {
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'save_intervention_method',
                experiment_id: experimentId,
                method_type: methodData.type || 'metacognitive',
                method_name: methodData.name || '',
                description: methodData.description || '',
                is_active: methodData.isActive || 1
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('개입 방법 저장 성공:', result.message);
            return result;
        } else {
            throw new Error(result.error);
        }
        
    } catch (error) {
        console.error('개입 방법 저장 실패:', error);
        throw error;
    }
}

// 추적 설정 저장
async function saveTrackingConfig(experimentId, configData) {
    try {
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'save_tracking_config',
                experiment_id: experimentId,
                config_name: configData.name || '',
                description: configData.description || '',
                tracking_type: configData.trackingType || 'performance',
                data_source: configData.dataSource || '',
                collection_frequency: configData.collectionFrequency || 'weekly',
                is_active: configData.isActive || 1
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('추적 설정 저장 성공:', result.message);
            return result;
        } else {
            throw new Error(result.error);
        }
        
    } catch (error) {
        console.error('추적 설정 저장 실패:', error);
        throw error;
    }
}

// 그룹 배정 저장
async function saveGroupAssignment(experimentId, userId, groupType, options = {}) {
    try {
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'save_group_assignment',
                experiment_id: experimentId,
                user_id: userId,
                group_type: groupType,
                intervention_method_id: options.interventionMethodId || '',
                teacher_id: options.teacherId || '',
                assigned_by: options.assignedBy || ''
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('그룹 배정 저장 성공:', result.message);
            return result;
        } else {
            throw new Error(result.error);
        }
        
    } catch (error) {
        console.error('그룹 배정 저장 실패:', error);
        throw error;
    }
}

// DB 연결 정보 저장
async function saveDatabaseConnection(experimentId, tableName, options = {}) {
    try {
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'save_database_connection',
                experiment_id: experimentId,
                table_name: tableName,
                database_name: options.databaseName || 'mathking',
                purpose: options.purpose || '',
                conditions: typeof options.conditions === 'object' ? JSON.stringify(options.conditions) : options.conditions || ''
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('DB 연결 정보 저장 성공:', result.message);
            return result;
        } else {
            throw new Error(result.error);
        }
        
    } catch (error) {
        console.error('DB 연결 정보 저장 실패:', error);
        throw error;
    }
}

// 실험 결과 저장
async function saveExperimentResult(experimentId, resultData) {
    try {
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'save_experiment_result',
                experiment_id: experimentId,
                result_type: resultData.type || 'analysis',
                result_title: resultData.title || '',
                result_content: resultData.content || '',
                result_data: typeof resultData.data === 'object' ? JSON.stringify(resultData.data) : resultData.data || '',
                author_id: resultData.authorId || 0,
                collection_date: resultData.collectionDate || ''
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('실험 결과 저장 성공:', result.message);
            return result;
        } else {
            throw new Error(result.error);
        }
        
    } catch (error) {
        console.error('실험 결과 저장 실패:', error);
        throw error;
    }
}

// 가설 저장
async function saveHypothesis(experimentId, hypothesisText, options = {}) {
    try {
        console.log('가설 저장 API 호출 시도:', {
            experimentId,
            hypothesisText,
            options
        });
        
        const requestData = {
            action: 'save_hypothesis',
            experiment_id: experimentId,
            hypothesis_text: hypothesisText,
            hypothesis_type: options.type || 'primary',
            author_id: options.authorId || ''
        };
        
        console.log('가설 저장 요청 파라미터:', requestData);
        
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(requestData)
        });
        
        console.log('가설 저장 응답 상태:', response.status);
        
        const responseText = await response.text();
        console.log('가설 저장 응답 텍스트:', responseText);
        
        const result = JSON.parse(responseText);
        console.log('가설 저장 파싱된 결과:', result);
        
        if (result.success) {
            console.log('가설 저장 성공:', result.message);
            return result;
        } else {
            throw new Error(result.error);
        }
        
    } catch (error) {
        console.error('가설 저장 실패:', error);
        throw error;
    }
}

// 실험 활동 로그 기록
async function logExperimentActivity(experimentId, logType, logMessage, options = {}) {
    try {
        const response = await fetch('src/api/database_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'log_experiment_activity',
                experiment_id: experimentId,
                log_type: logType,
                log_message: logMessage,
                log_data: typeof options.logData === 'object' ? JSON.stringify(options.logData) : options.logData || '',
                user_id: options.userId || ''
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('실험 활동 로그 기록 성공:', result.message);
            return result;
        } else {
            throw new Error(result.error);
        }
        
    } catch (error) {
        console.error('실험 활동 로그 기록 실패:', error);
        throw error;
    }
}

// 실험 관리 유틸리티 함수들
class ExperimentManager {
    constructor() {
        this.currentExperiment = null;
        this.experimentId = null;
    }
    
    // 현재 실험 설정
    setCurrentExperiment(experiment) {
        this.currentExperiment = experiment;
        this.experimentId = experiment.id;
    }
    
    // 실험 생성 및 저장
    async createExperiment(experimentData) {
        try {
            const result = await saveExperiment(experimentData);
            
            if (result.success) {
                this.experimentId = result.experiment_id;
                this.currentExperiment = { ...experimentData, id: this.experimentId };
                
                // 실험 생성 로그 기록
                await logExperimentActivity(this.experimentId, 'start', '실험이 생성되었습니다.', {
                    userId: experimentData.createdBy,
                    logData: { experiment_name: experimentData.name }
                });
                
                return result;
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('실험 생성 실패:', error);
            throw error;
        }
    }
    
    // 실험에 DB 연결 추가
    async addDatabaseToExperiment(tableName, conditions = null) {
        if (!this.experimentId) {
            throw new Error('실험을 먼저 생성해주세요.');
        }
        
        try {
            const connectionData = {
                databaseName: 'mathking',
                purpose: '데이터 추적을 위한 테이블 연결',
                conditions: conditions
            };
            
            const result = await saveDatabaseConnection(this.experimentId, tableName, connectionData);
            
            if (result.success) {
                // DB 연결 로그 기록
                await logExperimentActivity(this.experimentId, 'modify', `DB 테이블 연결: ${tableName}`, {
                    logData: { table_name: tableName, conditions: conditions }
                });
            }
            
            return result;
        } catch (error) {
            console.error('DB 연결 추가 실패:', error);
            throw error;
        }
    }
    
    // 실험 결과 기록
    async recordExperimentResult(resultData) {
        if (!this.experimentId) {
            throw new Error('실험을 먼저 생성해주세요.');
        }
        
        try {
            const result = await saveExperimentResult(this.experimentId, resultData);
            
            if (result.success) {
                // 결과 기록 로그
                await logExperimentActivity(this.experimentId, 'modify', `실험 결과 기록: ${resultData.title}`, {
                    logData: { result_type: resultData.type, result_id: result.result_id }
                });
            }
            
            return result;
        } catch (error) {
            console.error('실험 결과 기록 실패:', error);
            throw error;
        }
    }
}

// 전역 실험 관리자 인스턴스
const experimentManager = new ExperimentManager();

// 전역 함수로 등록
window.saveExperiment = saveExperiment;
window.getExperiment = getExperiment;
window.getExperimentsList = getExperimentsList;
window.saveInterventionMethod = saveInterventionMethod;
window.saveTrackingConfig = saveTrackingConfig;
window.saveGroupAssignment = saveGroupAssignment;
window.saveDatabaseConnection = saveDatabaseConnection;
window.saveExperimentResult = saveExperimentResult;
window.saveHypothesis = saveHypothesis;
window.logExperimentActivity = logExperimentActivity;
window.experimentManager = experimentManager;