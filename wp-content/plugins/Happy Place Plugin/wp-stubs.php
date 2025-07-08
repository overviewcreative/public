<?php
// WordPress function stubs for static analysis

function add_action(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): void {}
function add_filter(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): void {}
function admin_url(string $path = ''): string { return ''; }
function check_ajax_referer(string $action, string $query_arg = '_wpnonce'): bool { return true; }
function current_user_can(string $capability, mixed ...$args): bool { return true; }
function esc_url(string $url): string { return ''; }
function get_template_directory(): string { return ''; }
function get_the_ID(): int { return 0; }
function is_singular(string $post_type = ''): bool { return true; }
function plugin_dir_path(string $file): string { return ''; }
function plugins_url(string $path = '', string $plugin = ''): string { return ''; }
function wp_create_nonce(string $action): string { return ''; }
function wp_die(string $message): void {}
function wp_enqueue_script(string $handle, string $src = '', array $deps = [], string $ver = '', bool $in_footer = false): void {}
function wp_enqueue_style(string $handle, string $src = '', array $deps = [], string $ver = '', string $media = 'all'): void {}
function wp_mkdir_p(string $target): bool { return true; }
function wp_next_scheduled(string $hook): int { return 0; }
function wp_redirect(string $location, int $status = 302): void {}
function wp_schedule_event(int $timestamp, string $recurrence, string $hook, array $args = []): bool { return true; }
function wp_send_json_error(array $data = []): void {}
function wp_send_json_success(array $data = []): void {}
function wp_upload_dir(): array { return []; }

// Define common WP globals and constants
define('WP_CONTENT_DIR', '');
