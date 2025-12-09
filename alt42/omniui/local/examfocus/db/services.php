<?php
/**
 * ExamFocus 플러그인 웹서비스 정의
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_examfocus_get_recommendation' => [
        'classname'   => 'local_examfocus\external\recommendation',
        'methodname'  => 'get_recommendation',
        'classpath'   => 'local/examfocus/classes/external/recommendation.php',
        'description' => 'Get exam preparation mode recommendation for a user',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'local/examfocus:view_recommendations',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'local_examfocus_accept_recommendation' => [
        'classname'   => 'local_examfocus\external\recommendation',
        'methodname'  => 'accept_recommendation',
        'classpath'   => 'local/examfocus/classes/external/recommendation.php',
        'description' => 'Accept a recommended study mode',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'local/examfocus:view_recommendations',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'local_examfocus_dismiss_recommendation' => [
        'classname'   => 'local_examfocus\external\recommendation',
        'methodname'  => 'dismiss_recommendation',
        'classpath'   => 'local/examfocus/classes/external/recommendation.php',
        'description' => 'Dismiss a recommendation for cooldown period',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'local/examfocus:view_recommendations',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'local_examfocus_get_user_preferences' => [
        'classname'   => 'local_examfocus\external\preferences',
        'methodname'  => 'get_user_preferences',
        'classpath'   => 'local/examfocus/classes/external/preferences.php',
        'description' => 'Get user preferences for exam focus',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'local/examfocus:view_recommendations',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    
    'local_examfocus_update_user_preferences' => [
        'classname'   => 'local_examfocus\external\preferences',
        'methodname'  => 'update_user_preferences',
        'classpath'   => 'local/examfocus/classes/external/preferences.php',
        'description' => 'Update user preferences for exam focus',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'local/examfocus:view_recommendations',
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ]
];

$services = [
    'ExamFocus Service' => [
        'functions' => [
            'local_examfocus_get_recommendation',
            'local_examfocus_accept_recommendation',
            'local_examfocus_dismiss_recommendation',
            'local_examfocus_get_user_preferences',
            'local_examfocus_update_user_preferences'
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'examfocus'
    ]
];