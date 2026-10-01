import { expect, test } from "@playwright/test";

const username = "playwright";
const password = "playwright-password";

test.afterEach(async ({ page }) => {
    page.on("dialog", (dialog) => dialog.accept());
    await page.goto("about:blank");
});

async function loginAndOpenMenu(page, menuName) {
    await page.goto("/admin.php?action=login");
    await page.getByLabel("Username").fill(username);
    await page.getByLabel("Password").fill(password);
    await Promise.all([
        page.waitForURL(/admin\.php$/),
        page.getByRole("button", { name: "Log In" }).click(),
    ]);
    await page.goto(`/admin.php?action=edit-menu&menu=${menuName}`);
    await expect(page.getByRole("heading", { name: `Edit Menu: ${menuName}` })).toBeVisible();
}

function directItems(list) {
    return list.locator(":scope > .relay-menu-item");
}

async function directLabels(list) {
    return directItems(list)
        .locator(":scope > .relay-menu-item-row .menu-item-label")
        .evaluateAll((inputs) => inputs.map((input) => input.value));
}

async function dragBefore(page, source, target) {
    const sourceBox = await source.boundingBox();
    const targetBox = await target.boundingBox();

    if (!sourceBox || !targetBox) {
        throw new Error("Drag source or target is not visible");
    }

    await page.mouse.move(
        sourceBox.x + sourceBox.width / 2,
        sourceBox.y + sourceBox.height / 2
    );
    await page.mouse.down();
    await page.mouse.move(
        sourceBox.x + sourceBox.width / 2 + 10,
        sourceBox.y + sourceBox.height / 2 + 10,
        { steps: 4 }
    );
    await page.waitForTimeout(50);
    await page.mouse.move(
        targetBox.x + targetBox.width / 2,
        targetBox.y + 2,
        { steps: 16 }
    );
    await page.waitForTimeout(50);
    await page.mouse.up();
    await page.waitForTimeout(200);
}

test("dragging reorders top-level subtrees and nested siblings", async ({ page }) => {
    await loginAndOpenMenu(page, "drag-menu");
    const root = page.locator("#menu-items");

    await dragBefore(
        page,
        directItems(root).nth(1).locator(":scope > .relay-menu-item-row .relay-drag-handle"),
        directItems(root).nth(0).locator(":scope > .relay-menu-item-row")
    );

    await expect.poll(() => directLabels(root)).toEqual([
        "Parent B",
        "Parent A",
        "Standalone",
    ]);
    const movedChildren = directItems(root).nth(0).locator(":scope > .relay-menu-children");
    await expect.poll(() => directLabels(movedChildren)).toEqual(["B One", "B Two"]);

    await dragBefore(
        page,
        directItems(movedChildren).nth(1).locator(":scope > .relay-menu-item-row .relay-drag-handle"),
        directItems(movedChildren).nth(0).locator(":scope > .relay-menu-item-row")
    );
    await expect.poll(() => directLabels(movedChildren)).toEqual(["B Two", "B One"]);
    await expect(page.locator("#save-status")).toHaveText("Unsaved changes");
});

test("dragging cannot move an item to another parent", async ({ page }) => {
    await loginAndOpenMenu(page, "drag-menu");
    const root = page.locator("#menu-items");
    const firstChildren = directItems(root).nth(0).locator(":scope > .relay-menu-children");
    const secondChildren = directItems(root).nth(1).locator(":scope > .relay-menu-children");

    await dragBefore(
        page,
        directItems(firstChildren).nth(1).locator(":scope > .relay-menu-item-row .relay-drag-handle"),
        directItems(secondChildren).nth(0).locator(":scope > .relay-menu-item-row")
    );

    await expect.poll(() => directLabels(firstChildren)).toEqual(["A One", "A Two"]);
    await expect.poll(() => directLabels(secondChildren)).toEqual(["B One", "B Two"]);
    await expect(page.locator("#save-status")).toBeEmpty();
});

test("movement buttons preserve subtrees and indent or outdent whole nodes", async ({ page }) => {
    await loginAndOpenMenu(page, "hierarchy-menu");
    const root = page.locator("#menu-items");

    await expect(directItems(root).first().locator(":scope > .relay-menu-item-row .move-up")).toBeDisabled();
    await expect(directItems(root).last().locator(":scope > .relay-menu-item-row .move-down")).toBeDisabled();

    await directItems(root).nth(0).locator(":scope > .relay-menu-item-row .move-down").click();
    await expect.poll(() => directLabels(root)).toEqual(["Second", "First", "Third"]);
    await expect(
        directItems(root).nth(1).locator(":scope > .relay-menu-children > .relay-menu-item")
    ).toHaveCount(1);

    await directItems(root).nth(1).locator(":scope > .relay-menu-item-row .move-up").click();
    await directItems(root).nth(1).locator(":scope > .relay-menu-item-row .indent-in").click();
    await expect.poll(() => directLabels(root)).toEqual(["First", "Third"]);

    const firstChildren = directItems(root).nth(0).locator(":scope > .relay-menu-children");
    await expect.poll(() => directLabels(firstChildren)).toEqual(["First Child", "Second"]);
    await expect(
        directItems(firstChildren).nth(1).locator(":scope > .relay-menu-children > .relay-menu-item")
    ).toHaveCount(1);

    await directItems(firstChildren).nth(1).locator(":scope > .relay-menu-item-row .indent-out").click();
    await expect.poll(() => directLabels(root)).toEqual(["First", "Second", "Third"]);
    await expect(
        directItems(root).nth(1).locator(":scope > .relay-menu-children > .relay-menu-item")
    ).toHaveCount(1);
});

test("dragging marks dirty and saving persists the new order", async ({ page }) => {
    await loginAndOpenMenu(page, "save-menu");
    const root = page.locator("#menu-items");

    await dragBefore(
        page,
        directItems(root).nth(1).locator(":scope > .relay-menu-item-row .relay-drag-handle"),
        directItems(root).nth(0).locator(":scope > .relay-menu-item-row")
    );
    await expect(page.locator("#save-status")).toHaveText("Unsaved changes");

    const responsePromise = page.waitForResponse(
        (response) => response.url().includes("action=save-menu") && response.request().method() === "POST"
    );
    await page.getByRole("button", { name: "Save Menu" }).click();
    const response = await responsePromise;
    expect(response.ok()).toBe(true);
    await expect(page.locator("#save-status")).toContainText("Saved successfully");

    await page.reload();
    await expect.poll(() => directLabels(root)).toEqual(["Two", "One"]);
});

test("empty and single-item menus maintain useful boundary states", async ({ page }) => {
    await loginAndOpenMenu(page, "empty-menu");
    await expect(page.locator(".relay-menu-empty")).toBeVisible();

    await page.getByRole("button", { name: "Add Item" }).click();
    await expect(page.locator(".relay-menu-empty")).toHaveCount(0);
    await expect(page.locator(".relay-menu-item")).toHaveCount(1);
    await expect(page.locator(".menu-item-label")).toBeFocused();

    page.once("dialog", (dialog) => dialog.accept());
    await page.getByRole("button", { name: "Delete menu item" }).click();
    await expect(page.locator(".relay-menu-item")).toHaveCount(0);
    await expect(page.locator(".relay-menu-empty")).toBeVisible();

    await page.goto("/admin.php?action=edit-menu&menu=single-menu");
    const onlyItem = page.locator("#menu-items > .relay-menu-item");
    await expect(onlyItem.locator(".move-up")).toBeDisabled();
    await expect(onlyItem.locator(".move-down")).toBeDisabled();
    await expect(onlyItem.locator(".indent-in")).toBeDisabled();
    await expect(onlyItem.locator(".indent-out")).toBeDisabled();
});

test("deep nesting remains structured without horizontal overflow", async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await loginAndOpenMenu(page, "deep-menu");

    await expect(page.locator(".relay-menu-item")).toHaveCount(6);
    await expect
        .poll(() =>
            page.locator(".relay-menu-item").evaluateAll((items) =>
                items.map((item) => Number(item.dataset.indent))
            )
        )
        .toEqual([0, 1, 2, 3, 4, 5]);

    const overflow = await page.evaluate(() => ({
        body: document.documentElement.scrollWidth > innerWidth,
        menu: document.querySelector("#menu-items").scrollWidth >
            document.querySelector("#menu-items").clientWidth,
        rows: Array.from(document.querySelectorAll(".relay-menu-item-row")).some(
            (row) => row.scrollWidth > row.clientWidth
        ),
    }));
    expect(overflow).toEqual({ body: false, menu: false, rows: false });
});
