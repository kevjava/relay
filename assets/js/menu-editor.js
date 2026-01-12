/**
 * Relay Menu Editor - Vanilla JavaScript
 *
 * Handles menu item manipulation: add, delete, reorder, indent/outdent, and AJAX save.
 */

(function () {
  "use strict";

  // Track unsaved changes
  let hasUnsavedChanges = false;

  // Get CSRF token from meta tag
  function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute("content") : "";
  }

  // Get base path for URL construction
  function getBasePath() {
    const input = document.getElementById('base-path');
    return input ? input.value : "";
  }

  // Get all menu items
  function getMenuItems() {
    return Array.from(document.querySelectorAll(".relay-menu-item"));
  }

  // Mark menu as having unsaved changes
  function markUnsaved() {
    hasUnsavedChanges = true;

    // Show visual indicator
    const statusSpan = document.getElementById("save-status");
    if (statusSpan) {
      statusSpan.textContent = "Unsaved changes";
      statusSpan.className = "relay-status-unsaved";
    }
  }

  // Update item indices
  function updateIndices() {
    const items = getMenuItems();
    items.forEach((item, index) => {
      item.setAttribute("data-index", index);
    });
  }

  // Update item visual indentation
  function updateIndentation(item) {
    const indent = parseInt(item.getAttribute("data-indent") || 0);
    const content = item.querySelector(".relay-menu-item-content");
    if (content) {
      content.style.marginLeft = indent * 30 + "px";
    }
  }

  // Add new menu item
  function addMenuItem() {
    const container = document.getElementById("menu-items");
    const emptyMessage = container.querySelector(".relay-menu-empty");

    if (emptyMessage) {
      emptyMessage.remove();
    }

    const items = getMenuItems();
    const index = items.length;

    const itemHtml = `
            <div class="relay-menu-item" data-index="${index}" data-indent="0">
                <div class="relay-menu-item-controls">
                    <button type="button" class="relay-button-icon move-up" title="Move Up">↑</button>
                    <button type="button" class="relay-button-icon move-down" title="Move Down">↓</button>
                    <button type="button" class="relay-button-icon indent-out" title="Outdent">←</button>
                    <button type="button" class="relay-button-icon indent-in" title="Indent">→</button>
                </div>
                <div class="relay-menu-item-content" style="margin-left: 0px;">
                    <input type="text" class="menu-item-label" value="" placeholder="Label">
                    <input type="text" class="menu-item-url" value="" placeholder="URL">
                    <button type="button" class="relay-button relay-button-danger delete-item">Delete</button>
                </div>
            </div>
        `;

    container.insertAdjacentHTML("beforeend", itemHtml);
    updateIndices();
    markUnsaved();

    // Focus on the new item's label input
    const newItems = getMenuItems();
    const newItem = newItems[newItems.length - 1];
    const labelInput = newItem.querySelector(".menu-item-label");
    if (labelInput) {
      labelInput.focus();
    }
  }

  // Delete menu item
  function deleteMenuItem(item) {
    if (confirm("Are you sure you want to delete this menu item?")) {
      item.remove();
      updateIndices();
      markUnsaved();

      // Show empty message if no items left
      const items = getMenuItems();
      if (items.length === 0) {
        const container = document.getElementById("menu-items");
        container.innerHTML =
          '<p class="relay-menu-empty">No menu items. Click "Add Item" to create one.</p>';
      }
    }
  }

  // Move item up
  function moveItemUp(item) {
    const prev = item.previousElementSibling;
    if (prev && prev.classList.contains("relay-menu-item")) {
      item.parentNode.insertBefore(item, prev);
      updateIndices();
      markUnsaved();
    }
  }

  // Move item down
  function moveItemDown(item) {
    const next = item.nextElementSibling;
    if (next && next.classList.contains("relay-menu-item")) {
      item.parentNode.insertBefore(next, item);
      updateIndices();
      markUnsaved();
    }
  }

  // Indent item (increase nesting level)
  function indentItem(item) {
    const currentIndent = parseInt(item.getAttribute("data-indent") || 0);
    const index = parseInt(item.getAttribute("data-index"));

    // Find previous item
    if (index > 0) {
      const items = getMenuItems();
      const prevItem = items[index - 1];
      const prevIndent = parseInt(prevItem.getAttribute("data-indent") || 0);

      // Can only indent up to one level deeper than previous item
      if (currentIndent <= prevIndent) {
        const newIndent = currentIndent + 1;
        item.setAttribute("data-indent", newIndent);
        updateIndentation(item);
        markUnsaved();
      }
    }
  }

  // Outdent item (decrease nesting level)
  function outdentItem(item) {
    const currentIndent = parseInt(item.getAttribute("data-indent") || 0);

    if (currentIndent > 0) {
      const newIndent = currentIndent - 1;
      item.setAttribute("data-indent", newIndent);
      updateIndentation(item);
      markUnsaved();
    }
  }

  // Collect menu data from DOM
  function collectMenuData() {
    const items = getMenuItems();
    const flatItems = [];

    items.forEach((item) => {
      const label = item.querySelector(".menu-item-label").value.trim();
      const url = item.querySelector(".menu-item-url").value.trim();
      const indent = parseInt(item.getAttribute("data-indent") || 0);

      if (label && url) {
        flatItems.push({ label, url, indent });
      }
    });

    // Convert flat structure to nested
    return flatToNested(flatItems);
  }

  // Convert flat array with indent to nested structure
  function flatToNested(flatItems) {
    const nested = [];
    const stack = [];

    flatItems.forEach((item) => {
      const indent = item.indent;
      delete item.indent;

      // Find correct parent level
      while (stack.length > 0 && stack[stack.length - 1].indent >= indent) {
        stack.pop();
      }

      if (stack.length === 0) {
        // Top level item
        nested.push(item);
        stack.push({ indent, item });
      } else {
        // Child item
        const parent = stack[stack.length - 1].item;

        if (!parent.children) {
          parent.children = [];
        }

        parent.children.push(item);
        stack.push({ indent, item });
      }
    });

    return nested;
  }

  // Save menu via AJAX
  function saveMenu() {
    const menuName = document.getElementById("menu-name").value;
    const menuData = collectMenuData();
    const statusSpan = document.getElementById("save-status");

    statusSpan.textContent = "Saving...";
    statusSpan.className = "relay-status-saving";

    // Create form data
    const formData = new FormData();
    formData.append("csrf_token", getCsrfToken());
    formData.append("menu_name", menuName);
    formData.append("menu_data", JSON.stringify(menuData));

    const basePath = getBasePath();
    fetch(basePath + "/admin.php?action=save-menu", {
      body: formData,
      method: "POST",
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          hasUnsavedChanges = false;
          statusSpan.textContent = "✓ Saved successfully";
          statusSpan.className = "relay-status-success";
          setTimeout(() => {
            statusSpan.textContent = "";
            statusSpan.className = "";
          }, 3000);
        } else {
          if (data.error_type === 'session_expired') {
            statusSpan.textContent = "✗ " + data.error;
            statusSpan.className = "relay-status-error";

            if (confirm(data.error + "\n\nClick OK to go to the login page now.")) {
              window.location.href = data.redirect || (basePath + "/admin.php?action=login");
            }
          } else if (data.error_type && data.error_type.startsWith('csrf_')) {
            statusSpan.textContent = "✗ " + data.error;
            statusSpan.className = "relay-status-error";

            if (confirm(data.error + "\n\nWould you like to refresh the page now? (Unsaved changes will be lost)")) {
              window.location.reload();
            }
          } else {
            statusSpan.textContent = "✗ Error: " + (data.error || "Unknown error");
            statusSpan.className = "relay-status-error";
          }
        }
      })
      .catch((error) => {
        statusSpan.textContent = "✗ Error: " + error.message;
        statusSpan.className = "relay-status-error";
      });
  }

  // Event delegation for menu item controls
  document.getElementById("menu-items").addEventListener("click", function (e) {
    const target = e.target;
    const item = target.closest(".relay-menu-item");

    if (!item) return;

    if (target.classList.contains("move-up")) {
      moveItemUp(item);
    } else if (target.classList.contains("move-down")) {
      moveItemDown(item);
    } else if (target.classList.contains("indent-in")) {
      indentItem(item);
    } else if (target.classList.contains("indent-out")) {
      outdentItem(item);
    } else if (target.classList.contains("delete-item")) {
      deleteMenuItem(item);
    }
  });

  // Add item button
  document
    .getElementById("add-menu-item")
    .addEventListener("click", addMenuItem);

  // Save button
  document.getElementById("save-menu").addEventListener("click", saveMenu);

  // Keyboard shortcuts
  document.addEventListener("keydown", function (e) {
    // Ctrl+S or Cmd+S to save
    if ((e.ctrlKey || e.metaKey) && e.key === "s") {
      e.preventDefault();
      saveMenu();
    }
  });

  // Track input changes in menu items
  document.getElementById("menu-items").addEventListener("input", function (e) {
    const target = e.target;
    if (target.classList.contains("menu-item-label") || target.classList.contains("menu-item-url")) {
      markUnsaved();
    }
  });

  // Warn before navigating away with unsaved changes
  window.addEventListener("beforeunload", function (e) {
    if (hasUnsavedChanges) {
      e.preventDefault();
      // Modern browsers ignore custom messages and show a generic message
      e.returnValue = "";
      return "";
    }
  });
})();
