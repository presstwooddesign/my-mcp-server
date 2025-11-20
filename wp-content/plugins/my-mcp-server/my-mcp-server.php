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

// NEW: Create Post Ability (UPDATED WITH DATES)
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
                        'description' => __('Post status (draft, publish, pending, future)', 'my-mcp-server'),
                        'default' => 'draft',
                    ),
                    'excerpt' => array(
                        'type' => 'string',
                        'description' => __('Post excerpt (optional)', 'my-mcp-server'),
                    ),
                    'post_date' => array(
                        'type' => 'string',
                        'description' => __('Post publish date in Y-m-d H:i:s format (optional). Use for scheduling future posts.', 'my-mcp-server'),
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

// NEW: Update Post Ability (UPDATED WITH DATES)
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
                    'post_date' => array(
                        'type' => 'string',
                        'description' => __('Post publish date in Y-m-d H:i:s format (optional)', 'my-mcp-server'),
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

    // Handle post date if provided
    if (isset($input['post_date']) && !empty($input['post_date'])) {
        $post_date = sanitize_text_field($input['post_date']);

        // Validate date format
        $date_obj = DateTime::createFromFormat('Y-m-d H:i:s', $post_date);
        if ($date_obj && $date_obj->format('Y-m-d H:i:s') === $post_date) {
            $post_data['post_date'] = $post_date;
            $post_data['post_date_gmt'] = get_gmt_from_date($post_date);

            // If date is in the future and status is publish, change to future
            if (strtotime($post_date) > current_time('timestamp') && $post_data['post_status'] === 'publish') {
                $post_data['post_status'] = 'future';
            }
        } else {
            return new WP_Error('invalid_date', __('Invalid date format. Use Y-m-d H:i:s (e.g., 2025-12-31 14:30:00)', 'my-mcp-server'));
        }
    }

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

    // Handle post date if provided
    if (isset($input['post_date']) && !empty($input['post_date'])) {
        $post_date = sanitize_text_field($input['post_date']);

        // Validate date format
        $date_obj = DateTime::createFromFormat('Y-m-d H:i:s', $post_date);
        if ($date_obj && $date_obj->format('Y-m-d H:i:s') === $post_date) {
            $post_data['post_date'] = $post_date;
            $post_data['post_date_gmt'] = get_gmt_from_date($post_date);

            // If date is in the future and status is publish, change to future
            if (strtotime($post_date) > current_time('timestamp') &&
                (isset($post_data['post_status']) && $post_data['post_status'] === 'publish')) {
                $post_data['post_status'] = 'future';
            }
        } else {
            return new WP_Error('invalid_date', __('Invalid date format. Use Y-m-d H:i:s (e.g., 2025-12-31 14:30:00)', 'my-mcp-server'));
        }
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