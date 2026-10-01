import { spawn, spawnSync } from "node:child_process";
import {
    cpSync,
    mkdirSync,
    mkdtempSync,
    rmSync,
    writeFileSync,
} from "node:fs";
import { tmpdir } from "node:os";
import { basename, join, resolve } from "node:path";

const repositoryRoot = resolve(import.meta.dirname, "../..");
const applicationRoot = mkdtempSync(join(tmpdir(), "relay-playwright-"));
const port = process.env.RELAY_PLAYWRIGHT_PORT || "8788";
const runtimeEntries = [
    "admin.php",
    "index.php",
    "error-404.php",
    "lib",
    "assets",
    "themes",
    "vendor",
    "config",
];

for (const entry of runtimeEntries) {
    cpSync(join(repositoryRoot, entry), join(applicationRoot, basename(entry)), {
        recursive: true,
    });
}

const hashResult = spawnSync(
    "php",
    ["-r", 'echo password_hash("playwright-password", PASSWORD_BCRYPT);'],
    { encoding: "utf8" }
);

if (hashResult.status !== 0 || !hashResult.stdout) {
    throw new Error(`Unable to create Playwright password hash: ${hashResult.stderr}`);
}

const menus = {
    "drag-menu": [
        {
            label: "Parent A",
            url: "/a",
            children: [
                { label: "A One", url: "/a/one" },
                { label: "A Two", url: "/a/two" },
            ],
        },
        {
            label: "Parent B",
            url: "/b",
            children: [
                { label: "B One", url: "/b/one" },
                { label: "B Two", url: "/b/two" },
            ],
        },
        { label: "Standalone", url: "/standalone" },
    ],
    "hierarchy-menu": [
        {
            label: "First",
            url: "/first",
            children: [{ label: "First Child", url: "/first/child" }],
        },
        {
            label: "Second",
            url: "/second",
            children: [{ label: "Second Child", url: "/second/child" }],
        },
        { label: "Third", url: "/third" },
    ],
    "save-menu": [
        { label: "One", url: "/one" },
        { label: "Two", url: "/two" },
    ],
    "empty-menu": [],
    "single-menu": [{ label: "Only", url: "/only" }],
};

let deepItem = { label: "Level 6", url: "/level-6" };
for (let depth = 5; depth >= 1; depth--) {
    deepItem = {
        label: `Level ${depth}`,
        url: `/level-${depth}`,
        children: [deepItem],
    };
}
menus["deep-menu"] = [deepItem];

const configDirectory = join(applicationRoot, "config");
const sessionsDirectory = join(applicationRoot, "sessions");
mkdirSync(sessionsDirectory, { recursive: true });
writeFileSync(
    join(configDirectory, "users.json"),
    JSON.stringify(
        {
            playwright: {
                password_hash: hashResult.stdout,
                role: "admin",
            },
        },
        null,
        2
    )
);
writeFileSync(
    join(configDirectory, "settings.json"),
    JSON.stringify({ active_theme: "default", site_name: "Relay Test" }, null, 2)
);

for (const [name, data] of Object.entries(menus)) {
    writeFileSync(
        join(configDirectory, `${name}.json`),
        JSON.stringify(data, null, 2)
    );
}

const phpServer = spawn(
    "php",
    [
        "-d",
        `session.save_path=${sessionsDirectory}`,
        "-S",
        `127.0.0.1:${port}`,
        "-t",
        applicationRoot,
    ],
    { stdio: "inherit" }
);
let shuttingDown = false;

function shutdown(exitCode = 0) {
    if (shuttingDown) {
        return;
    }

    shuttingDown = true;
    if (!phpServer.killed) {
        phpServer.kill("SIGTERM");
    }
    rmSync(applicationRoot, { recursive: true, force: true });
    process.exit(exitCode);
}

phpServer.on("error", (error) => {
    console.error(error);
    shutdown(1);
});
phpServer.on("exit", (code, signal) => {
    if (!shuttingDown) {
        console.error(`PHP server exited unexpectedly (${signal || code}).`);
        shutdown(code || 1);
    }
});
process.on("SIGINT", () => shutdown());
process.on("SIGTERM", () => shutdown());
