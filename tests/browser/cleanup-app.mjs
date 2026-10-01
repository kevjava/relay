import { rmSync } from "node:fs";

export default function cleanupApp() {
    rmSync("/tmp/relay-playwright-relay-cms", {
        recursive: true,
        force: true,
    });
}