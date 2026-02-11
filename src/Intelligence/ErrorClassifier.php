<?php
namespace BugSneak\Intelligence;

if (!defined('ABSPATH')) {
    exit;
}

class ErrorClassifier
{
    /**
     * Pattern Rules
     * Supports:
     * - regex
     * - literal match
     * - weight (confidence)
     * - severity
     * - tags
     */
    protected static array $rules = [

        [
            'type'      => 'regex',
            'pattern'   => '/Allowed memory size exhausted/i',
            'category'  => 'Memory Exhaustion',
            'severity'  => 'critical',
            'weight'    => 90,
            'tags'      => ['memory', 'php', 'fatal'],
            'suggestion'=> 'Increase WP_MEMORY_LIMIT in wp-config.php or inspect memory-heavy plugins.',
        ],

        [
            'type'      => 'regex',
            'pattern'   => '/Maximum execution time of \d+ seconds exceeded/i',
            'category'  => 'Execution Timeout',
            'severity'  => 'critical',
            'weight'    => 85,
            'tags'      => ['timeout', 'performance'],
            'suggestion'=> 'Increase max_execution_time in php.ini or optimize long-running processes.',
        ],

        [
            'type'      => 'regex',
            'pattern'   => '/Call to undefined function/i',
            'category'  => 'Missing Function',
            'severity'  => 'high',
            'weight'    => 80,
            'tags'      => ['dependency', 'plugin', 'fatal'],
            'suggestion'=> 'Ensure required plugin or dependency is active and loaded properly.',
        ],
        
        [
            'type'      => 'regex',
            'pattern'   => '/Call to undefined method/i',
            'category'  => 'Missing Method',
            'severity'  => 'high',
            'weight'    => 80,
            'tags'      => ['dependency', 'code-error', 'fatal'],
            'suggestion'=> 'Plugin or class version mismatch. Verify compatibility and updates.',
        ],

        [
            'type'      => 'regex',
            'pattern'   => '/Class not found/i',
            'category'  => 'Missing Class',
            'severity'  => 'high',
            'weight'    => 80,
            'tags'      => ['autoloader', 'dependency', 'fatal'],
            'suggestion'=> 'Autoloader issue or missing dependency. Check plugin installation integrity.',
        ],

        [
            'type'      => 'regex',
            'pattern'   => '/Cannot redeclare (class|function)/i',
            'category'  => 'Redeclaration Conflict',
            'severity'  => 'high',
            'weight'    => 75,
            'tags'      => ['conflict', 'plugin'],
            'suggestion'=> 'Possible plugin conflict or duplicate file inclusion.',
        ],

        [
            'type'      => 'regex',
            'pattern'   => '/WordPress database error/i',
            'category'  => 'Database Error',
            'severity'  => 'critical',
            'weight'    => 95,
            'tags'      => ['database', 'sql'],
            'suggestion'=> 'Verify database credentials and check for corrupted tables.',
        ],
        
        [
            'type'      => 'regex',
            'pattern'   => '/Base table or view not found/i',
            'category'  => 'Missing Database Table',
            'severity'  => 'critical',
            'weight'    => 90,
            'tags'      => ['database', 'schema'],
            'suggestion'=> 'Plugin activation may have failed. Try reactivating plugin.',
        ],
        
        [
            'type'      => 'regex',
            'pattern'   => '/MySQL server has gone away/i',
            'category'  => 'Database Connection Lost',
            'severity'  => 'critical',
            'weight'    => 95,
            'tags'      => ['database', 'timeout'],
            'suggestion'=> 'Database server timed out or packet size is too large (max_allowed_packet).',
        ],

        [
            'type'      => 'regex',
            'pattern'   => '/Parse error/i',
            'category'  => 'Syntax Error',
            'severity'  => 'critical',
            'weight'    => 100,
            'tags'      => ['php', 'syntax'],
            'suggestion'=> 'Check PHP syntax in the referenced file.',
        ],
        
        [
            'type'      => 'regex',
            'pattern'   => '/TypeError/i',
            'category'  => 'Type Mismatch',
            'severity'  => 'high',
            'weight'    => 70,
            'tags'      => ['php', 'types'],
            'suggestion'=> 'Invalid parameter type passed to function. Review recent code changes.',
        ],
        
        [
            'type'      => 'regex',
            'pattern'   => '/Too few arguments to function/i',
            'category'  => 'Argument Mismatch',
            'severity'  => 'high',
            'weight'    => 70,
            'tags'      => ['php', 'signature'],
            'suggestion'=> 'Function signature mismatch. Check plugin version compatibility.',
        ],

        [
            'type'      => 'regex',
            'pattern'   => '/Undefined (array key|index|offset)/i',
            'category'  => 'Undefined Array Key',
            'severity'  => 'low',
            'weight'    => 50,
            'tags'      => ['notice', 'php'],
            'suggestion'=> 'Check if array key/index exists before accessing it.',
        ],
        
        [
            'type'      => 'literal',
            'pattern'   => 'Trying to get property of non-object',
            'category'  => 'Invalid Object Access',
            'severity'  => 'medium',
            'weight'    => 60,
            'tags'      => ['php', 'logic'],
            'suggestion'=> 'Object expected but null returned. Check conditional logic.',
        ],
        
        [
            'type'      => 'regex',
            'pattern'   => '/Call to a member function .* on null/i',
            'category'  => 'Null Reference',
            'severity'  => 'high',
            'weight'    => 75,
            'tags'      => ['php', 'null'],
            'suggestion'=> 'Object may not be initialized properly.',
        ],
        
        [
            'type'      => 'regex',
            'pattern'   => '/Cannot modify header information/i',
            'category'  => 'Header Output Issue',
            'severity'  => 'medium',
            'weight'    => 65,
            'tags'      => ['php', 'headers'],
            'suggestion'=> 'Output was sent before headers. Check for whitespace or echo statements.',
        ],
        
        [
            'type'      => 'literal',
            'pattern'   => 'rest_no_route',
            'category'  => 'Invalid REST Route',
            'severity'  => 'medium',
            'weight'    => 55,
            'tags'      => ['api', 'rest'],
            'suggestion'=> 'Ensure REST route is registered correctly.',
        ],
        
        [
            'type'      => 'regex',
            'pattern'   => '/session_start\(\)/i',
            'category'  => 'Session Conflict',
            'severity'  => 'medium',
            'weight'    => 50,
            'tags'      => ['php', 'session'],
            'suggestion'=> 'Session may already be started by another plugin.',
        ],
        
        [
            'type'      => 'literal',
            'pattern'   => 'GD library',
            'category'  => 'Image Processing Error',
            'severity'  => 'medium',
            'weight'    => 50,
            'tags'      => ['images', 'extension'],
            'suggestion'=> 'Ensure GD or Imagick extension is enabled.',
        ],
        
        [
            'type'      => 'literal',
            'pattern'   => 'Deprecated',
            'category'  => 'Deprecated Function Usage',
            'severity'  => 'low',
            'weight'    => 30,
            'tags'      => ['php', 'deprecated'],
            'suggestion'=> 'Plugin may need update for current PHP version.',
        ],
        
        [
            'type'      => 'regex',
            'pattern'   => '/cURL error/i',
            'category'  => 'HTTP Connection Failure',
            'severity'  => 'high',
            'weight'    => 70,
            'tags'      => ['http', 'network'],
            'suggestion'=> 'External API request failed. Check server connectivity or firewall settings.',
        ],
        
        [
            'type'      => 'regex',
            'pattern'   => '/Call to (private|protected) method/i',
            'category'  => 'Visibility Violation',
            'severity'  => 'high',
            'weight'    => 70,
            'tags'      => ['php', 'oop'],
            'suggestion'=> 'Attempting to access a protected/private class method. Check plugin compatibility.',
        ],
        
        [
            'type'      => 'literal',
            'pattern'   => 'must be compatible with',
            'category'  => 'Declaration Incompatibility',
            'severity'  => 'medium',
            'weight'    => 60,
            'tags'      => ['php', 'oop', 'strict'],
            'suggestion'=> 'Child class method signature does not match parent. Update plugin/theme.',
        ],
        
        [
            'type'      => 'regex',
            'pattern'   => '/Division by zero/i',
            'category'  => 'Math Error',
            'severity'  => 'medium',
            'weight'    => 50,
            'tags'      => ['php', 'math'],
            'suggestion'=> 'Code attempted to divide by zero. Check logical conditions.',
        ],
        
        [
            'type'      => 'regex',
            'pattern'   => '/json_decode/i',
            'category'  => 'JSON Parsing Error',
            'severity'  => 'medium',
            'weight'    => 50,
            'tags'      => ['php', 'json', 'data'],
            'suggestion'=> 'Failed to decode JSON data. Response might be malformed or empty.',
        ],
        
        [
            'type'      => 'regex',
            'pattern'   => '/Input variables exceeded/i',
            'category'  => 'Max Input Vars Exceeded',
            'severity'  => 'high',
            'weight'    => 65,
            'tags'      => ['php', 'server', 'limit'],
            'suggestion'=> 'Menu/Form too large. Increase max_input_vars in php.ini.',
        ],
        
        [
            'type'      => 'regex',
            'pattern'   => '/failed to open stream: Permission denied/i',
            'category'  => 'File Write Permission',
            'severity'  => 'high',
            'weight'    => 85,
            'tags'      => ['filesystem', 'permissions'],
            'suggestion'=> 'Server cannot write to file/folder. Check chmod/chown settings.',
        ],
        
        [
            'type'      => 'regex',
            'pattern'   => '/failed to open stream/i',
            'category'  => 'Missing File',
            'severity'  => 'high',
            'weight'    => 80,
            'tags'      => ['filesystem', 'missing'],
            'suggestion'=> 'Verify file path and ensure plugin/theme files exist.',
        ],
        
        [
            'type'      => 'regex',
            'pattern'   => '/move_uploaded_file/i',
            'category'  => 'Upload Failure',
            'severity'  => 'high',
            'weight'    => 80,
            'tags'      => ['filesystem', 'upload'],
            'suggestion'=> 'Failed to move uploaded file. Check upload_tmp_dir or permissions.',
        ],
    ];

    /**
     * Classify error message
     */
    public static function classify(string $message): array
    {
        $matches = [];
        
        // Allow advanced users to override/extend rules
        $rules = apply_filters( 'bugsneak_classification_rules', self::$rules );

        foreach ($rules as $rule) {

            $matched = false;

            if ($rule['type'] === 'regex') {
                $matched = preg_match($rule['pattern'], $message);
            }

            if ($rule['type'] === 'literal') {
                $matched = stripos($message, $rule['pattern']) !== false;
            }

            if ($matched) {
                $matches[] = $rule;
            }
        }

        if (empty($matches)) {
            return [
                'category'   => 'Unclassified',
                'severity'   => 'unknown',
                'confidence' => 0,
                'tags'       => [],
                'suggestion' => 'Review stack trace and recent changes.',
            ];
        }

        // Sort by weight (highest first)
        usort($matches, function ($a, $b) {
            return $b['weight'] <=> $a['weight'];
        });

        $primary = $matches[0];

        return [
            'category'   => $primary['category'],
            'severity'   => $primary['severity'],
            'confidence' => $primary['weight'],
            'tags'       => self::collectTags($matches),
            'suggestion' => $primary['suggestion'],
        ];
    }

    /**
     * Merge tags from all matched rules
     */
    protected static function collectTags(array $matches): array
    {
        $tags = [];

        foreach ($matches as $match) {
            $tags = array_merge($tags, $match['tags']);
        }

        return array_values(array_unique($tags));
    }
}
