import { expect, test } from "@playwright/test";

test("password change form exposes password manager autocomplete attributes", async ({ page }) => {
    await page.goto("/admin.php?action=login");
    await page.getByLabel("Username").fill("playwright");
    await page.getByLabel("Password").fill("playwright-password");
    await Promise.all([
        page.waitForURL(/admin\.php$/),
        page.getByRole("button", { name: "Log In" }).click(),
    ]);

    await expect(page.locator("#old_password")).toHaveAttribute(
        "autocomplete",
        "current-password"
    );
    await expect(page.locator("#new_password")).toHaveAttribute(
        "autocomplete",
        "new-password"
    );
    await expect(page.locator("#confirm_password")).toHaveAttribute(
        "autocomplete",
        "new-password"
    );
});