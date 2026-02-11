<?php
/**
 * Diagnostic Overlay Template for BugSneak.
 * Flare-inspired dark error page with Slate & Indigo palette.
 *
 * @package BugSneak\Core\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bugsneak_env = array(
	'php'   => PHP_VERSION,
	'wp'    => $GLOBALS['wp_version'] ?? 'Unknown',
	'os'    => php_uname( 's' ),
	'theme' => wp_get_theme()->get( 'Name' ),
);

$bugsneak_snippet  = $data['code_snippet'] ?? array();
$bugsneak_severity = $data['severity'] ?? 'Fatal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>BugSneak — <?php echo esc_html( $data['message'] ); ?></title>
    <?php // phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
    <?php // phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
	<?php /* Inline resources required during fatal error recovery when wp_enqueue is unavailable */ ?>
	<script src="<?php echo esc_url( BUGSNEAK_URL . 'assets/vendor/tailwindcss.js' ); ?>"></script>
	<link href="<?php echo esc_url( BUGSNEAK_URL . 'assets/vendor/inter.css' ); ?>" rel="stylesheet">
	<link href="<?php echo esc_url( BUGSNEAK_URL . 'assets/vendor/material-icons.css' ); ?>" rel="stylesheet">
    <?php // phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
    <?php // phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
	<style>
		* { margin: 0; padding: 0; box-sizing: border-box; }
		body { background: #0f172a; color: #f8fafc; font-family: 'Inter', sans-serif; min-height: 100vh; }
		.custom-scrollbar::-webkit-scrollbar { width: 6px; }
		.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
		.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #334155; border-radius: 20px; }
	</style>
</head>
<body class="min-h-screen flex flex-col">

	<?php
	$bugsneak_nav_logo = defined( 'BUGSNEAK_URL' ) ? BUGSNEAK_URL . 'logo-text-new.svg' : '';

	// Run Classification (Layer 2) with Context Intelligence
	$bugsneak_classification = array(
		'category'   => 'Unclassified',
		'suggestion' => 'Review stack trace for details.',
		'severity'   => 'unknown',
	);
	if ( class_exists( '\BugSneak\Intelligence\ErrorClassifier' ) ) {
		$bugsneak_context            = \BugSneak\Intelligence\ContextBuilder::build();
		$bugsneak_context['culprit'] = $data['culprit'] ?? null;

		$bugsneak_classification = \BugSneak\Intelligence\ErrorClassifier::classify( $data['message'], $bugsneak_context );
	}
	?>

	<!-- Header Bar -->
	<header class="bg-[#1e293b] border-b border-[#334155] h-12 flex items-center justify-between px-6 shrink-0">
		<div class="flex items-center gap-2.5">
			<?php if ( $bugsneak_nav_logo ) : ?>
				<img src="<?php echo esc_url( $bugsneak_nav_logo ); ?>" alt="BugSneak" class="h-6 w-auto" style="filter: brightness(0) invert(1);">
			<?php else : ?>
				<span class="font-bold text-sm tracking-tight text-white">BugSneak</span>
			<?php endif; ?>
			<span class="text-[9px] font-bold text-[#94a3b8] bg-[#334155] px-1.5 py-0.5 rounded ml-1 uppercase tracking-wider">Diagnostic</span>
		</div>
		<div class="text-[10px] text-[#64748b] font-mono uppercase tracking-widest hidden sm:block">Intercepted · <?php echo esc_html( strtoupper( $bugsneak_severity ) ); ?></div>
	</header>

	<main class="flex-1 max-w-5xl mx-auto w-full px-6 py-8 space-y-6">

		<!-- Error Badge + Message -->
		<div class="space-y-3">
			<div class="flex items-center gap-2.5">
				<span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold text-white uppercase tracking-wider shadow-lg <?php echo strtolower( $bugsneak_severity ) === 'warning' ? 'bg-[#f59e0b] shadow-[#f59e0b]/20' : 'bg-[#ef4444] shadow-[#ef4444]/20'; ?>">
					<?php echo esc_html( strtoupper( $bugsneak_severity ) ); ?>
				</span>
				<?php if ( $bugsneak_classification['category'] !== 'Unclassified' ) : ?>
					<span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold text-[#e2e8f0] bg-[#334155] border border-[#475569] uppercase tracking-wider">
						<?php echo esc_html( $bugsneak_classification['category'] ); ?>
					</span>
				<?php endif; ?>
				<span class="text-[10px] text-[#64748b] font-mono tracking-wider">v1.3 Engine</span>
			</div>

			<h1 class="text-xl md:text-2xl font-bold text-white leading-tight break-words">
				<?php echo esc_html( $data['message'] ); ?>
			</h1>

			<div class="flex items-center gap-2 text-[12px] font-mono text-[#94a3b8] bg-[#1e293b] px-3 py-2 rounded-lg border border-[#334155] w-fit max-w-full truncate">
				<span class="material-icons text-[16px] text-[#6366f1]">folder_open</span>
				<span class="truncate"><?php echo esc_html( $data['file'] ); ?></span>
				<span class="text-[#475569]">·</span>
				<span class="font-bold text-white">Line <?php echo (int) $data['line']; ?></span>
			</div>
		</div>

		<!-- AI Insight + Security Note -->
		<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
			<div class="bg-[rgba(99,102,241,0.08)] border border-[rgba(99,102,241,0.15)] rounded-lg p-4 flex items-start gap-3">
				<div class="shrink-0 p-1.5 bg-[rgba(99,102,241,0.15)] rounded-lg">
					<span class="material-icons text-[#818cf8] text-xl">auto_fix_high</span>
				</div>
				<div class="space-y-1">
					<h3 class="text-[10px] font-bold text-[#818cf8] uppercase tracking-wider">Smart Analysis</h3>
					<p class="text-[13px] text-[#cbd5e1] leading-relaxed">
						<?php if ( $classification['category'] !== 'Unclassified' ) : ?>
							<strong><?php echo esc_html( $classification['category'] ); ?>:</strong>
							<?php echo esc_html( $classification['suggestion'] ); ?>
						<?php else : ?>
							Culprit: <code class="bg-[rgba(99,102,241,0.12)] px-1.5 py-0.5 rounded text-[#a5b4fc] font-bold text-[12px]"><?php echo esc_html( $data['culprit'] ); ?></code>.
							Review the code context below.
						<?php endif; ?>
					</p>
				</div>
			</div>

			<div class="bg-[#1e293b] border border-[#334155] rounded-lg p-4 flex items-center gap-3">
				<div class="shrink-0 p-1.5 bg-[rgba(245,158,11,0.12)] rounded-lg">
					<span class="material-icons text-[#fbbf24] text-xl">security</span>
				</div>
				<div class="space-y-0.5">
					<h3 class="text-[9px] font-bold text-[#fbbf24] uppercase tracking-wider">Security</h3>
					<p class="text-[11px] text-[#94a3b8] leading-relaxed">
						Visible only because <code class="text-[#e2e8f0]">WP_DEBUG</code> is active. Users see standard error page.
					</p>
				</div>
			</div>
		</div>

		<!-- Code Context -->
		<div class="space-y-2">
			<div class="flex items-center justify-between px-1">
				<h2 class="text-[10px] font-bold text-[#64748b] uppercase tracking-wider">Code Context</h2>
				<span class="text-[9px] text-[#475569] font-mono">±5 lines</span>
			</div>

			<div class="bg-[#0f172a] rounded-lg border border-[#334155] overflow-hidden">
				<div class="h-8 bg-[#1e293b] border-b border-[#334155] flex items-center px-4 justify-between shrink-0">
					<div class="flex items-center gap-1.5">
						<span class="w-2.5 h-2.5 rounded-full bg-[#ef4444]"></span>
						<span class="w-2.5 h-2.5 rounded-full bg-[#f59e0b]"></span>
						<span class="w-2.5 h-2.5 rounded-full bg-[#10b981]"></span>
						<span class="ml-3 text-[11px] text-[#64748b] font-mono"><?php echo esc_html( basename( $data['file'] ) ); ?></span>
					</div>
				</div>
				<div class="font-mono text-[12px] leading-5 overflow-x-auto text-[#cbd5e1] custom-scrollbar">
					<?php if ( ! empty( $bugsneak_snippet['lines'] ) ) : ?>
						<table class="w-full border-collapse">
							<tbody>
								<?php foreach ( $bugsneak_snippet['lines'] as $bugsneak_num => $bugsneak_line ) : ?>
									<?php $bugsneak_is_target = (int) $bugsneak_num === (int) $bugsneak_snippet['target']; ?>
									<tr class="<?php echo $bugsneak_is_target ? 'bg-[rgba(239,68,68,0.12)]' : ''; ?>">
										<td class="w-12 text-right pr-4 text-[#475569] select-none border-r border-[#334155] <?php echo $bugsneak_is_target ? 'text-white font-bold' : ''; ?>"><?php echo (int) $bugsneak_num; ?></td>
										<td class="pl-4 py-px <?php echo $bugsneak_is_target ? 'text-white font-bold border-l-2 border-[#ef4444]' : ''; ?>">
											<?php echo esc_html( $bugsneak_line ); ?>
											<?php if ( $bugsneak_is_target ) : ?>
												<span class="text-[#fca5a5] text-[10px] ml-4 italic">← error here</span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<div class="py-6 text-center text-[#475569] italic">Code context not available.</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- Environment Grid -->
		<div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
			<?php foreach ( $bugsneak_env as $bugsneak_label => $bugsneak_val ) : ?>
				<div class="p-4 bg-[#1e293b] rounded-lg border border-[#334155]">
					<span class="block text-[9px] font-bold text-[#64748b] uppercase tracking-wider mb-1"><?php echo esc_html( $bugsneak_label ); ?></span>
					<span class="text-[13px] font-semibold text-[#e2e8f0]"><?php echo esc_html( $bugsneak_val ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>

		<!-- Footer -->
		<footer class="pt-6 pb-4 text-center border-t border-[#334155]">
			<div class="inline-flex flex-col items-center gap-2">
				<p class="text-[9px] font-bold text-[#475569] uppercase tracking-[0.15em]">Engineered by BugSneak</p>
				<div class="flex gap-4">
					<button onclick="window.location.reload()" class="text-[10px] text-[#6366f1] font-bold uppercase hover:underline">Retry</button>
					<span class="text-[#334155]">|</span>
					<button onclick="window.print()" class="text-[10px] text-[#64748b] font-bold uppercase hover:underline">Print Report</button>
				</div>
			</div>
		</footer>
	</main>
</body>
</html>
<?php
