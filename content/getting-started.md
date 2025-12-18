---
title: Getting Started
date: 2024-12-17
---

# Getting Started with Relay

This guide will help you get up and running with Relay in minutes.

## Installation

1. **Clone or download** the Relay repository
2. **Run initialization**: `php admin-tools.php init`
3. **Start Docker container**: `docker-compose up -d`
4. **Visit your site** at `http://localhost:8080`

## Creating Content

Content in Relay is created using Markdown files in the `/content` directory:

1. Create a new `.md` file in the content directory
2. Add optional frontmatter for metadata
3. Write your content in Markdown
4. The page will be immediately available

### Example Content File

```markdown
---
title: My Page
date: 2024-12-17
author: John Doe
---

# My Page

This is my content written in Markdown.
```

## Managing Menus

1. Log in to the admin panel at `/admin.php`
2. Click on the menu you want to edit
3. Add, remove, or reorder menu items
4. Use the indent/outdent buttons to create nested menus
5. Click "Save Menu" when done

## Managing Users

User management is done through CLI tools:

```bash
# Create a new user
php admin-tools.php create-user username editor

# Reset a password
php admin-tools.php reset-password username

# List all users
php admin-tools.php list-users
```

## Next Steps

- Explore the [documentation](#) for advanced features
- Customize the theme by editing `index.php`
- Add your own CSS in `/assets/css/`
