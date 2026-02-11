<?php
/**
 * AI Processor for BugSneak.
 * Connects to Google Gemini or OpenAI ChatGPT for deep error analysis.
 *
 * @package BugSneak\Core
 */

namespace BugSneak\Core;

use BugSneak\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AIProcessor
 */
class AIProcessor {

	/**
	 * Analyze an error log using AI.
	 *
	 * @param array $log Error log data from the database.
	 * @return array|string|\WP_Error
	 */
	public static function analyze( $log ) {
		$enabled  = Settings::get( 'ai_enabled', false );
		$provider = Settings::get( 'ai_provider', 'gemini' );

		if ( ! $enabled ) {
			return new \WP_Error( 'ai_disabled', 'AI is not enabled in settings.' );
		}

		if ( 'openai' === $provider ) {
			return self::analyze_with_openai( $log );
		}

		return self::analyze_with_gemini( $log );
	}

	/**
	 * Analyze with Google Gemini.
	 *
	 * @param array $log Log data.
	 * @return array|string|\WP_Error
	 */
	private static function analyze_with_gemini( $log ) {
		$api_key = Settings::get( 'ai_gemini_key' );
		$model   = Settings::get( 'ai_gemini_model', 'gemini-2.0-flash' );

		if ( empty( $api_key ) ) {
			return new \WP_Error( 'ai_missing_key', 'Gemini API key is missing.' );
		}

		$url      = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$api_key}";
		$prompt   = self::build_prompt( $log );
		$response = wp_remote_post( $url, [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( [
				'contents' => [ [ 'parts' => [ [ 'text' => $prompt ] ] ] ],
				'generationConfig' => [
					'temperature'     => 0.2,
					'maxOutputTokens' => 1024,
				],
			] ),
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! empty( $body['error'] ) ) {
			return new \WP_Error( 'ai_api_error', $body['error']['message'] ?? 'Gemini API returned an error.' );
		}

		return $body['candidates'][0]['content']['parts'][0]['text'] ?? new \WP_Error( 'ai_empty', 'Gemini returned an empty response.' );
	}

	/**
	 * Analyze with OpenAI ChatGPT.
	 *
	 * @param array $log Log data.
	 * @return array|string|\WP_Error
	 */
	private static function analyze_with_openai( $log ) {
		$api_key = Settings::get( 'ai_openai_key' );
		$model   = Settings::get( 'ai_openai_model', 'gpt-4o-mini' );

		if ( empty( $api_key ) ) {
			return new \WP_Error( 'ai_missing_key', 'OpenAI API key is missing.' );
		}

		$prompt   = self::build_prompt( $log );
		$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			],
			'body'    => wp_json_encode( [
				'model'    => $model,
				'messages' => [
					[ 'role' => 'system', 'content' => 'You are a senior WordPress expert. Analyze PHP errors and provide concise explanations and fixes.' ],
					[ 'role' => 'user', 'content' => $prompt ],
				],
				'temperature' => 0.2,
				'max_tokens'  => 1024,
			] ),
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! empty( $body['error'] ) ) {
			return new \WP_Error( 'ai_api_error', $body['error']['message'] ?? 'OpenAI API returned an error.' );
		}

		return $body['choices'][0]['message']['content'] ?? new \WP_Error( 'ai_empty', 'OpenAI returned an empty response.' );
	}

	/**
	 * Build the prompt for LLMs.
	 *
	 * @param array $log Log data.
	 * @return string
	 */
	private static function build_prompt( $log ) {
		$snippet = json_decode( $log['code_snippet'], true );
		$prompt  = "ERROR: {$log['error_message']}\nTYPE: {$log['error_type']}\nFILE: {$log['file_path']} (Line {$log['line_number']})\n\n";

		if ( ! empty( $snippet['lines'] ) ) {
			$prompt .= "CODE CONTEXT:\n";
			foreach ( $snippet['lines'] as $num => $line ) {
				$mark = (int) $num === (int) $snippet['target'] ? '>>> ' : '    ';
				$prompt .= "{$mark}{$num}: {$line}\n";
			}
			$prompt .= "\n";
		}

		$prompt .= "Analyze why this happened and provide a specific fix suggestion. Keep it concise and technical.";
		return $prompt;
	}
}
