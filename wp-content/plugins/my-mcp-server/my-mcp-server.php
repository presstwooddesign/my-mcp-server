<?php
/*
 * Plugin Name: My MCP Server
 * Description: A plugin to experiment with AI functionalities.
 * Version: 1.0
 * Author: Your Name
 */
// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

// Register the ability category when the Abilities API is initialized
// Using the action hook wp_abilities_api_categories_init ensures the API fully loaded
add_action('wp_abilities_api_categories_init', 'my_mcp_server_register_ability_categories');
function my_mcp_server_register_ability_categories() {
    wp_register_ability_category('my-mcp-server-category', array(
        'label' => __( 'My Category', 'my-mcp-server' ),
        'description' => __( 'My category description.', 'my-mcp-server' ),
    ));
}

// Register the ability when the Abilities API is initialized
// Using the action hook wp_abilities_api_init ensures the API is fully loaded
add_action('wp_abilities_api_init', 'my_mcp_server_register_abilities');
function my_mcp_server_register_abilities() {

    // EXISTING: Get Site Title Ability
    wp_register_ability(
        'my-mcp-server/get-site-title',
        array(
            'label' => __('Get Site Title', 'my-mcp-server'),
            'description' => __('Retrieves the title of the current WordPress site.', 'my-mcp-server'),
            'category' => 'my-mcp-server-category',
            'input_schema' => array(),    // No inputs
            'output_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'site_title' => array(
                        'type' => 'string',
                        'description' => __('The site title.', 'my-mcp-server'),
                    ),
                ),
            ),
            'execute_callback' => 'my_mcp_server_get_site_title',
            'permission_callback' => 'my_mcp_server_permission_check'
        )
    );

    // NEW: List Posts Ability
    wp_register_ability(
        'my-mcp-server/list-posts',
        array(
            'label' => __('List Posts', 'my-mcp-server'),
            'description' => __('Returns a list of posts with their ID, title, status, and date.', 'my-mcp-server'),
            'category' => 'my-mcp-server-category',
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'posts_per_page' => array(
                        'type' => 'integer',
                        'description' => __('Number of posts to return (default: 10, max: 100)', 'my-mcp-server'),
                        'default' => 10,
                    ),
                    'post_status' => array(
                        'type' => 'string',
                        'description' => __('Filter by post status (publish, draft, pending, etc.)', 'my-mcp-server'),
                        'default' => 'any',
                    ),
                ),
            ),
            'output_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'posts' => array(
                        'type' => 'array',
                        'description' => __('Array of posts', 'my-mcp-server'),
                    ),
                    'total' => array(
                        'type' => 'integer',
                        'description' => __('Total number of posts matching criteria', 'my-mcp-server'),
                    ),
                ),
            ),
            'execute_callback' => 'my_mcp_server_list_posts',
            'permission_callback' => 'my_mcp_server_permission_check'
        )
    );

    // NEW: Get Single Post Ability
    wp_register_ability(
        'my-mcp-server/get-post',
        array(
            'label' => __('Get Post', 'my-mcp-server'),
            'description' => __('Retrieves a single post by ID with full content.', 'my-mcp-server'),
            'category' => 'my-mcp-server-category',
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'post_id' => array(
                        'type' => 'integer',
                        'description' => __('The ID of the post to retrieve', 'my-mcp-server'),
                    ),
                ),
                'required' => array('post_id'),
            ),
            'output_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'id' => array('type' => 'integer'),
                    'title' => array('type' => 'string'),
                    'content' => array('type' => 'string'),
                    'excerpt' => array('type' => 'string'),
                    'status' => array('type' => 'string'),
                    'date' => array('type' => 'string'),
                    'author' => array('type' => 'string'),
                ),
            ),
            'execute_callback' => 'my_mcp_server_get_post',
            'permission_callback' => 'my_mcp_server_permission_check'
        )
    );

    // NEW: Create Post Ability
    wp_register_ability(
        'my-mcp-server/create-post',
        array(
            'label' => __('Create Post', 'my-mcp-server'),
            'description' => __('Creates a new WordPress post.', 'my-mcp-server'),
            'category' => 'my-mcp-server-category',
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'title' => array(
                        'type' => 'string',
                        'description' => __('Post title', 'my-mcp-server'),
                    ),
                    'content' => array(
                        'type' => 'string',
                        'description' => __('Post content', 'my-mcp-server'),
                    ),
                    'status' => array(
                        'type' => 'string',
                        'description' => __('Post status (draft, publish, pending)', 'my-mcp-server'),
                        'default' => 'draft',
                    ),
                    'excerpt' => array(
                        'type' => 'string',
                        'description' => __('Post excerpt (optional)', 'my-mcp-server'),
                    ),
                ),
                'required' => array('title', 'content'),
            ),
            'output_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'post_id' => array(
                        'type' => 'integer',
                        'description' => __('ID of the created post', 'my-mcp-server'),
                    ),
                    'edit_url' => array(
                        'type' => 'string',
                        'description' => __('URL to edit the post', 'my-mcp-server'),
                    ),
                ),
            ),
            'execute_callback' => 'my_mcp_server_create_post',
            'permission_callback' => 'my_mcp_server_permission_check_can_edit_posts'
        )
    );

    // NEW: Update Post Ability
    wp_register_ability(
        'my-mcp-server/update-post',
        array(
            'label' => __('Update Post', 'my-mcp-server'),
            'description' => __('Updates an existing WordPress post.', 'my-mcp-server'),
            'category' => 'my-mcp-server-category',
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'post_id' => array(
                        'type' => 'integer',
                        'description' => __('ID of the post to update', 'my-mcp-server'),
                    ),
                    'title' => array(
                        'type' => 'string',
                        'description' => __('New post title (optional)', 'my-mcp-server'),
                    ),
                    'content' => array(
                        'type' => 'string',
                        'description' => __('New post content (optional)', 'my-mcp-server'),
                    ),
                    'status' => array(
                        'type' => 'string',
                        'description' => __('New post status (optional)', 'my-mcp-server'),
                    ),
                    'excerpt' => array(
                        'type' => 'string',
                        'description' => __('New post excerpt (optional)', 'my-mcp-server'),
                    ),
                ),
                'required' => array('post_id'),
            ),
            'output_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'post_id' => array(
                        'type' => 'integer',
                        'description' => __('ID of the updated post', 'my-mcp-server'),
                    ),
                    'edit_url' => array(
                        'type' => 'string',
                        'description' => __('URL to edit the post', 'my-mcp-server'),
                    ),
                ),
            ),
            'execute_callback' => 'my_mcp_server_update_post',
            'permission_callback' => 'my_mcp_server_permission_check_can_edit_posts'
        )
    );

    // NEW: Delete Post Ability
    wp_register_ability(
        'my-mcp-server/delete-post',
        array(
            'label' => __('Delete Post', 'my-mcp-server'),
            'description' => __('Deletes a WordPress post (moves to trash or deletes permanently).', 'my-mcp-server'),
            'category' => 'my-mcp-server-category',
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'post_id' => array(
                        'type' => 'integer',
                        'description' => __('ID of the post to delete', 'my-mcp-server'),
                    ),
                    'force_delete' => array(
                        'type' => 'boolean',
                        'description' => __('If true, permanently delete. If false, move to trash.', 'my-mcp-server'),
                        'default' => false,
                    ),
                ),
                'required' => array('post_id'),
            ),
            'output_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'success' => array(
                        'type' => 'boolean',
                        'description' => __('Whether the deletion was successful', 'my-mcp-server'),
                    ),
                    'message' => array(
                        'type' => 'string',
                        'description' => __('Status message', 'my-mcp-server'),
                    ),
                ),
            ),
            'execute_callback' => 'my_mcp_server_delete_post',
            'permission_callback' => 'my_mcp_server_permission_check_can_delete_posts'
        )
    );
}

// EXISTING: Permission callback function
function my_mcp_server_permission_check() {
    return is_user_logged_in(); // Only logged in users can access this ability
}

// NEW: Additional permission callbacks
function my_mcp_server_permission_check_can_edit_posts() {
    return current_user_can('edit_posts');
}

function my_mcp_server_permission_check_can_delete_posts() {
    return current_user_can('delete_posts');
}

// EXISTING: Define a callback function for the ability
function my_mcp_server_get_site_title( $input = array() ) {
    return array(
        'site_title' => get_bloginfo('name')
    );
}

// NEW: Execute callback functions for post management
function my_mcp_server_list_posts( $input = array() ) {
    $posts_per_page = isset($input['posts_per_page']) ? min((int)$input['posts_per_page'], 100) : 10;
    $post_status = isset($input['post_status']) ? sanitize_text_field($input['post_status']) : 'any';

    $args = array(
        'posts_per_page' => $posts_per_page,
        'post_status' => $post_status,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    $query = new WP_Query($args);
    $posts = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $posts[] = array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'status' => get_post_status(),
                'date' => get_the_date('Y-m-d H:i:s'),
                'author' => get_the_author(),
                'excerpt' => get_the_excerpt(),
            );
        }
        wp_reset_postdata();
    }

    return array(
        'posts' => $posts,
        'total' => $query->found_posts,
    );
}

function my_mcp_server_get_post( $input = array() ) {
    if (!isset($input['post_id'])) {
        return new WP_Error('missing_post_id', __('Post ID is required', 'my-mcp-server'));
    }

    $post = get_post((int)$input['post_id']);

    if (!$post) {
        return new WP_Error('post_not_found', __('Post not found', 'my-mcp-server'));
    }

    return array(
        'id' => $post->ID,
        'title' => $post->post_title,
        'content' => $post->post_content,
        'excerpt' => $post->post_excerpt,
        'status' => $post->post_status,
        'date' => $post->post_date,
        'author' => get_the_author_meta('display_name', $post->post_author),
    );
}

function my_mcp_server_create_post( $input = array() ) {
    if (!isset($input['title']) || !isset($input['content'])) {
        return new WP_Error('missing_required_fields', __('Title and content are required', 'my-mcp-server'));
    }

    $post_data = array(
        'post_title' => sanitize_text_field($input['title']),
        'post_content' => wp_kses_post($input['content']),
        'post_status' => isset($input['status']) ? sanitize_text_field($input['status']) : 'draft',
        'post_excerpt' => isset($input['excerpt']) ? sanitize_text_field($input['excerpt']) : '',
    );

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        return $post_id;
    }

    return array(
        'post_id' => $post_id,
        'edit_url' => get_edit_post_link($post_id, 'raw'),
    );
}

function my_mcp_server_update_post( $input = array() ) {
    if (!isset($input['post_id'])) {
        return new WP_Error('missing_post_id', __('Post ID is required', 'my-mcp-server'));
    }

    $post_id = (int)$input['post_id'];
    $post = get_post($post_id);

    if (!$post) {
        return new WP_Error('post_not_found', __('Post not found', 'my-mcp-server'));
    }

    $post_data = array('ID' => $post_id);

    if (isset($input['title'])) {
        $post_data['post_title'] = sanitize_text_field($input['title']);
    }

    if (isset($input['content'])) {
        $post_data['post_content'] = wp_kses_post($input['content']);
    }

    if (isset($input['status'])) {
        $post_data['post_status'] = sanitize_text_field($input['status']);
    }

    if (isset($input['excerpt'])) {
        $post_data['post_excerpt'] = sanitize_text_field($input['excerpt']);
    }

    $result = wp_update_post($post_data);

    if (is_wp_error($result)) {
        return $result;
    }

    return array(
        'post_id' => $post_id,
        'edit_url' => get_edit_post_link($post_id, 'raw'),
    );
}

function my_mcp_server_delete_post( $input = array() ) {
    if (!isset($input['post_id'])) {
        return new WP_Error('missing_post_id', __('Post ID is required', 'my-mcp-server'));
    }

    $post_id = (int)$input['post_id'];
    $force_delete = isset($input['force_delete']) ? (bool)$input['force_delete'] : false;

    $result = wp_delete_post($post_id, $force_delete);

    if (!$result) {
        return new WP_Error('delete_failed', __('Failed to delete post', 'my-mcp-server'));
    }

    return array(
        'success' => true,
        'message' => $force_delete
            ? __('Post permanently deleted', 'my-mcp-server')
            : __('Post moved to trash', 'my-mcp-server'),
    );
}

// EXISTING: Retrieve and execute the ability and show it in the admin notices
add_action( 'admin_notices', 'my_mcp_server_test_ability_admin_notice' );
function my_mcp_server_test_ability_admin_notice() {
    // Only show on the plugins page for testing
    $screen = get_current_screen();

    // Check we are on the plugins page
    if ( ! $screen || $screen->id !== 'plugins' ) {
        return;
    }

    // Check if the abilities API function exists
    if ( ! function_exists( 'wp_get_ability' ) ) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>Ability Test:</strong> Abilities API not loaded yet.</p>';
        echo '</div>';
        return;
    }

    // Get the ability class instance for the my-mcp-server/get-site-title ability
    $ability = wp_get_ability( 'my-mcp-server/get-site-title' );

    // Debug: Show if ability was found
    if ( ! $ability ) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>Ability Test:</strong> Ability not found. It may not be registered yet.</p>';
        echo '</div>';
        return;
    }

    // Execute the ability directly - the permission check happens inside execute()
    $result = $ability->execute();

    // Check if we got a WP_Error (permission denied or other error)
    if ( is_wp_error( $result ) ) {
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p><strong>Ability Test Error:</strong> ' . esc_html( $result->get_error_message() ) . '</p>';
        echo '</div>';
        return;
    }

    // Check if we got a valid result and extract the site title
    if ( is_array( $result ) && isset( $result['site_title'] ) ) {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>Ability Test:</strong> Site Title = "' . esc_html( $result['site_title'] ) . '"</p>';
        echo '</div>';
    } else {
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p><strong>Ability Test:</strong> Execution returned unexpected result.</p>';
        echo '</div>';
    }
}

// UPDATED: Create the MCP server with all abilities
add_action( 'mcp_adapter_init', function( $adapter ) {
    $adapter->create_server(
        'my-server-id',                    // Unique server identifier
        'my-mcp-server',                   // REST API namespace
        'mcp',                             // REST API route
        'My MCP Server',                   // Server name
        'WordPress MCP Server with post management abilities', // Updated description
        'v1.0.0',                          // Server version
        [
            \WP\MCP\Transport\HttpTransport::class, // Transport methods
        ],
        \WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,     // Error handler
        \WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class, // Observability handler
        [
            'my-mcp-server/get-site-title',
            'my-mcp-server/list-posts',
            'my-mcp-server/get-post',
            'my-mcp-server/create-post',
            'my-mcp-server/update-post',
            'my-mcp-server/delete-post',
        ],    // All abilities to expose as tools
        [],                                // Resources (optional)
        []                                 // Prompts (optional)
    );
});