import { defineConfig } from "@playwright/test";

export default defineConfig({
    testDir: "./tests/browser",
    globalTeardown: "./tests/browser/cleanup-app.mjs",
    fullyParallel: false,
    workers: 1,
    timeout: 30_000,
    expect: {
        timeout: 5_000,
    },
    use: {
        baseURL: "http://127.0.0.1:8788",
        trace: "retain-on-failure",
    },
    webServer: {
        command: "node tests/browser/setup-app.mjs && exec php -d session.save_path=/tmp/relay-playwright-relay-cms/sessions -S 127.0.0.1:8788 -t /tmp/relay-playwright-relay-cms",
        url: "http://127.0.0.1:8788/admin.php?action=login",
        reuseExistingServer: false,
        stderr: "ignore",
        stdout: "ignore",
        timeout: 30_000,
    },
    projects: [
        {
            name: "chromium",
            use: {
                browserName: "chromium",
            },
        },
    ],
});
