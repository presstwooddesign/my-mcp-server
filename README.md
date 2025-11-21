# My MCP Server (WordPress Abilities API Demo)

**My MCP Server** is a simple demonstration plugin built to explore the WordPress **Abilities API** and the **MCP Adapter**.

This plugin registers a custom server that exposes a set of abilities for interacting with WordPress content—such as retrieving posts, creating posts, updating posts, and deleting posts.

Base code and instructions came from [this great guide](https://wsform.com/how-to-create-an-mcp-server-in-wordpress-with-the-abilities-api-and-mcp-adapter/) from Mark Westguard

---

## Important Notice (Read This First)

This plugin is intended **for testing and educational purposes only**.  
It is **NOT** designed for production use and **should not be installed on live websites**.

Reasons you should not use this in production:

- No security hardening
- Minimal permission logic
- No sanitization beyond basic WordPress defaults
- No nonce or API key restrictions
- Error handling is intentionally minimal for clarity
- The Abilities API itself is experimental

**Use at your own risk.**

---

## How to Use

Setup and tested with:

- WordPress 6.9 RC2 (6.9-RC2-612)
- WordPress Studio 1.6.3 
- WordPress MCP Adapter 0.1.0 ([https://github.com/WordPress/mcp-adapter](https://github.com/WordPress/mcp-adapter))
- Claude Desktop 1.0.734 (b8f837)

Basic instructions:

- Set up a new local WordPress 6.9 site
- Install this plugin
- Set up an Application Password (Users > Your username > Application Passwords > (Called 'My MCP Server') (Again directly from Mark's article [here](https://wsform.com/how-to-create-an-mcp-server-in-wordpress-with-the-abilities-api-and-mcp-adapter/))
- Set up a local MCP. I used Claude, code below to add to Local MCP Servers (Settings > Developer > Edit Config) replace WP_API_URL, WP_API_USERNAME & WP_API_PASSWORD (Instructions from Mark Westguard [here](https://wsform.com/how-to-create-an-mcp-server-in-wordpress-with-the-abilities-api-and-mcp-adapter/) with more info and also VS Code example )
- Restart Claude
- In Claude, make sure 'my-mcp-server' is toggled on in the 'Search & tools menu'
- Done! Claude can now interact with your WordPress site with any of the listed abilities.

```php
{
    "mcpServers": {
        "my-mcp-server": {
            "command": "npx",
            "args": ["-y", "@automattic/mcp-wordpress-remote"],
            "env": {
                "WP_API_URL": "http://localhost:8881/wp-json/my-mcp-server/mcp",
                "WP_API_USERNAME": "admin",
                "WP_API_PASSWORD": "password goes here"
            }
        }
    }
}
```

---

## Features / Abilities

This plugin registers a custom ability category (`my-mcp-server-category`) and exposes the following abilities:

### ### 1. **Get Site Title**
**ID:** `my-mcp-server/get-site-title`  
Returns the current WordPress site title.

---

### 2. **List Posts**
**ID:** `my-mcp-server/list-posts`  
Returns a list of posts with IDs, titles, status, and dates.

**Inputs:**
- `posts_per_page` (integer, default 10, max 100)
- `post_status` (string, default: `any`)

---

### 3. **Get Post**
**ID:** `my-mcp-server/get-post`  
Retrieve a single post by ID, including full content.

**Inputs:**
- `post_id` (required)

---

### 4. **Create Post**
**ID:** `my-mcp-server/create-post`  
Create a new WordPress post.

**Inputs:**
- `title` (required)
- `content` (required)
- `status` (`draft`, `publish`, `pending`, `future`)
- `excerpt`
- `post_date` (Y-m-d H:i:s — supports scheduled posts)

---

### 5. **Update Post**
**ID:** `my-mcp-server/update-post`  
Update an existing WordPress post.

**Inputs:**
- `post_id` (required)
- `title`, `content`, `status`, `excerpt`, `post_date`

---

### 6. **Delete Post**
**ID:** `my-mcp-server/delete-post`  
Delete or trash a WordPress post.

**Inputs:**
- `post_id` (required)
- `force_delete` (boolean, default `false`)

---

## MCP Adapter Integration

The plugin registers a full MCP Server using:

```php
$adapter->create_server(
    'my-server-id',
    'my-mcp-server',
    'mcp',
    'My MCP Server',
    'WordPress MCP Server with post management abilities',
    'v1.0.0',
    [
        \WP\MCP\Transport\HttpTransport::class,
    ],
    \WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
    \WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
    [
        'my-mcp-server/get-site-title',
        'my-mcp-server/list-posts',
        'my-mcp-server/get-post',
        'my-mcp-server/create-post',
        'my-mcp-server/update-post',
        'my-mcp-server/delete-post',
    ],
    [],
    []
);