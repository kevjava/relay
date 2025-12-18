<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found - Relay</title>
    <link rel="stylesheet" href="/assets/css/relay.css">
    <style>
        .relay-404 {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 60vh;
            text-align: center;
            padding: 2rem;
        }
        .relay-404-content {
            max-width: 600px;
        }
        .relay-404-title {
            font-size: 6rem;
            font-weight: bold;
            color: #333;
            margin: 0;
            line-height: 1;
        }
        .relay-404-subtitle {
            font-size: 1.5rem;
            color: #666;
            margin: 1rem 0;
        }
        .relay-404-message {
            color: #888;
            margin: 1rem 0 2rem;
        }
        .relay-404-button {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: #0066cc;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        .relay-404-button:hover {
            background-color: #0052a3;
        }
    </style>
</head>
<body>
    <header class="relay-header">
        <div class="relay-container">
            <div class="relay-header-content">
                <div class="relay-logo">
                    <a href="/">Relay</a>
                </div>
            </div>
        </div>
    </header>

    <main class="relay-main">
        <div class="relay-404">
            <div class="relay-404-content">
                <h1 class="relay-404-title">404</h1>
                <h2 class="relay-404-subtitle">Page Not Found</h2>
                <p class="relay-404-message">
                    Sorry, the page you're looking for doesn't exist or has been moved.
                </p>
                <a href="/" class="relay-404-button">Go to Homepage</a>
            </div>
        </div>
    </main>

    <footer class="relay-footer">
        <div class="relay-container">
            <p>&copy; <?php echo date('Y'); ?> Relay CMS. Lightweight PHP content management.</p>
        </div>
    </footer>
</body>
</html>
