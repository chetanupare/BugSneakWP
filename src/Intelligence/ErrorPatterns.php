<?php
namespace BugSneak\Intelligence;

if (!defined('ABSPATH')) {
    exit;
}

class ErrorPatterns
{
    /**
     * Pattern definitions
     * Order matters — first match wins
     */
    protected static array $patterns = [

        // MEMORY
        [
            'match' => 'Allowed memory size exhausted',
            'category' => 'Memory Exhaustion',
            'severity' => 'critical',
            'suggestion' => 'Increase WP_MEMORY_LIMIT in wp-config.php or investigate memory-heavy plugins.',
        ],

        // TIMEOUT
        [
            'match' => 'Maximum execution time of',
            'category' => 'Execution Timeout',
            'severity' => 'critical',
            'suggestion' => 'Increase max_execution_time in php.ini or optimize long-running processes.',
        ],

        // UNDEFINED FUNCTION
        [
            'match' => 'Call to undefined function',
            'category' => 'Missing Function',
            'severity' => 'high',
            'suggestion' => 'Ensure required plugin or dependency is active and loaded properly.',
        ],

        // UNDEFINED METHOD
        [
            'match' => 'Call to undefined method',
            'category' => 'Missing Method',
            'severity' => 'high',
            'suggestion' => 'Plugin or class version mismatch. Verify compatibility and updates.',
        ],

        // UNDEFINED CLASS
        [
            'match' => 'Class not found',
            'category' => 'Missing Class',
            'severity' => 'high',
            'suggestion' => 'Autoloader issue or missing dependency. Check plugin installation integrity.',
        ],

        // REDECLARATION
        [
            'match' => 'Cannot redeclare',
            'category' => 'Class/Function Redeclaration',
            'severity' => 'high',
            'suggestion' => 'Duplicate plugin inclusion or conflict between plugins.',
        ],

        // TYPE ERROR
        [
            'match' => 'TypeError',
            'category' => 'Type Mismatch',
            'severity' => 'high',
            'suggestion' => 'Invalid parameter type passed to function. Review recent code changes.',
        ],

        // ARGUMENT COUNT
        [
            'match' => 'Too few arguments to function',
            'category' => 'Argument Mismatch',
            'severity' => 'high',
            'suggestion' => 'Function signature mismatch. Check plugin version compatibility.',
        ],

        // DATABASE
        [
            'match' => 'WordPress database error',
            'category' => 'Database Error',
            'severity' => 'critical',
            'suggestion' => 'Verify database credentials and check for corrupted tables.',
        ],

        // MISSING TABLE
        [
            'match' => 'Base table or view not found',
            'category' => 'Missing Database Table',
            'severity' => 'critical',
            'suggestion' => 'Plugin activation may have failed. Try reactivating plugin.',
        ],

        // SYNTAX ERROR
        [
            'match' => 'Parse error',
            'category' => 'Syntax Error',
            'severity' => 'critical',
            'suggestion' => 'Check the referenced file for PHP syntax issues.',
        ],

        // PERMISSION
        [
            'match' => 'Permission denied',
            'category' => 'File Permission Error',
            'severity' => 'high',
            'suggestion' => 'Check file/folder permissions on the server.',
        ],

        // FILE NOT FOUND
        [
            'match' => 'failed to open stream',
            'category' => 'Missing File',
            'severity' => 'high',
            'suggestion' => 'Verify file path and ensure plugin/theme files exist.',
        ],

        // NON OBJECT
        [
            'match' => 'Trying to get property of non-object',
            'category' => 'Invalid Object Access',
            'severity' => 'medium',
            'suggestion' => 'Object expected but null returned. Check conditional logic.',
        ],

        // ARRAY OFFSET
        [
            'match' => 'Undefined array key',
            'category' => 'Undefined Array Key',
            'severity' => 'low',
            'suggestion' => 'Check if array key exists before accessing it.',
        ],

        // NULL
        [
            'match' => 'Call to a member function on null',
            'category' => 'Null Reference',
            'severity' => 'high',
            'suggestion' => 'Object may not be initialized properly.',
        ],

        // HEADERS
        [
            'match' => 'Cannot modify header information',
            'category' => 'Header Output Issue',
            'severity' => 'medium',
            'suggestion' => 'Output was sent before headers. Check for whitespace or echo statements.',
        ],

        // REST ROUTE
        [
            'match' => 'rest_no_route',
            'category' => 'Invalid REST Route',
            'severity' => 'medium',
            'suggestion' => 'Ensure REST route is registered correctly.',
        ],

        // SESSION
        [
            'match' => 'session_start():',
            'category' => 'Session Conflict',
            'severity' => 'medium',
            'suggestion' => 'Session may already be started by another plugin.',
        ],

        // IMAGE
        [
            'match' => 'GD library',
            'category' => 'Image Processing Error',
            'severity' => 'medium',
            'suggestion' => 'Ensure GD or Imagick extension is enabled.',
        ],

        // PHP DEPRECATED
        [
            'match' => 'Deprecated',
            'category' => 'Deprecated Function Usage',
            'severity' => 'low',
            'suggestion' => 'Plugin may need update for current PHP version.',
        ],

    ];

    /**
     * Analyze error message
     */
    public static function analyze(string $message): array
    {
        foreach (self::$patterns as $pattern) {
            if (stripos($message, $pattern['match']) !== false) {
                return [
                    'category'   => $pattern['category'],
                    'severity'   => $pattern['severity'],
                    'suggestion' => $pattern['suggestion'],
                ];
            }
        }

        return [
            'category'   => 'Unclassified Error',
            'severity'   => 'unknown',
            'suggestion' => 'Review stack trace and recent code changes.',
        ];
    }
}
