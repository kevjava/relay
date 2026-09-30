/**
 * Relay Menu Editor - Vanilla JavaScript
 *
 * Handles menu item manipulation: add, delete, reorder, indent/outdent, and AJAX save.
 */

(function () {
  "use strict";

  // Track unsaved changes
  let hasUnsavedChanges = false;
  let sortableGroupIndex = 0;
  const sortableInstances = new Map();

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

  // Get only the menu items directly owned by a sibling list
  function getDirectItems(list) {
    return Array.from(list.children).filter((child) =>
      child.classList.contains("relay-menu-item")
    );
  }

  // Get an item's direct child list, if it has one
  function getChildList(item) {
    return Array.from(item.children).find((child) =>
      child.classList.contains("relay-menu-children")
    );
  }

  // Get or create the list that contains an item's children
  function getOrCreateChildList(item) {
    const existingList = getChildList(item);
    if (existingList) {
      return existingList;
    }

    const childList = document.createElement("ol");
    childList.className = "relay-menu-level relay-menu-children";
    item.appendChild(childList);
    return childList;
  }

  // Calculate an item's nesting depth from the nested list structure
  function getItemDepth(item) {
    let depth = 0;
    let parentList = item.parentElement;

    while (parentList && parentList.classList.contains("relay-menu-children")) {
      depth++;
      parentList = parentList.parentElement.parentElement;
    }

    return depth;
  }

  // Enable sorting within one sibling list without allowing cross-list moves
  function initializeSortable(list) {
    if (sortableInstances.has(list) || typeof window.Sortable === "undefined") {
      return;
    }

    const sortable = window.Sortable.create(list, {
      animation: 150,
      chosenClass: "relay-menu-item-chosen",
      draggable: ">.relay-menu-item",
      ghostClass: "relay-menu-item-ghost",
      group: {
        name: `relay-menu-level-${sortableGroupIndex++}`,
        pull: false,
        put: false,
      },
      handle: ".relay-drag-handle",
      onEnd(event) {
        if (
          event.from === event.to &&
          event.oldDraggableIndex !== event.newDraggableIndex
        ) {
          updateIndices();
          updateControlStates();
          markUnsaved();
        }
      },
    });

    sortableInstances.set(list, sortable);
  }

  // Initialize new lists and dispose instances whose lists were removed
  function refreshSortables() {
    sortableInstances.forEach((sortable, list) => {
      if (!list.isConnected) {
        sortable.destroy();
        sortableInstances.delete(list);
      }
    });

    document.querySelectorAll(".relay-menu-level").forEach(initializeSortable);
  }

  // Expose which movement controls are currently available
  function updateControlStates() {
    getMenuItems().forEach((item) => {
      const siblings = getDirectItems(item.parentElement);
      const position = siblings.indexOf(item);
      const moveUp = item.querySelector(":scope > .relay-menu-item-row .move-up");
      const moveDown = item.querySelector(":scope > .relay-menu-item-row .move-down");
      const indent = item.querySelector(":scope > .relay-menu-item-row .indent-in");
      const outdent = item.querySelector(":scope > .relay-menu-item-row .indent-out");

      moveUp.disabled = position <= 0;
      moveDown.disabled = position === siblings.length - 1;
      indent.disabled = position <= 0;
      outdent.disabled = !item.parentElement.classList.contains("relay-menu-children");
    });
  }

  // Finish a button-driven move and keep focus on the initiating control
  function completeMovement(control) {
    updateIndices();
    updateControlStates();
    refreshSortables();
    markUnsaved();

    if (control) {
      window.setTimeout(() => {
        const inverseControls = {
          "indent-in": ".indent-out",
          "indent-out": ".indent-in",
          "move-down": ".move-up",
          "move-up": ".move-down",
        };
        const controlClass = Object.keys(inverseControls).find((className) =>
          control.classList.contains(className)
        );
        const row = control.closest(".relay-menu-item-row");
        let focusTarget = control;

        if (focusTarget.disabled && controlClass && row) {
          focusTarget = row.querySelector(inverseControls[controlClass]);
        }

        if (!focusTarget || focusTarget.disabled) {
          focusTarget = row ? row.querySelector(".menu-item-label") : null;
        }

        if (focusTarget && focusTarget.isConnected) {
          focusTarget.focus();
        }
      }, 0);
    }
  }

  // Remove a child list after its final item moves away
  function removeEmptyChildList(list) {
    if (
      list.classList.contains("relay-menu-children") &&
      getDirectItems(list).length === 0
    ) {
      list.remove();
    }
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

  // Update item indices and indentation metadata
  function updateIndices() {
    const items = getMenuItems();
    items.forEach((item, index) => {
      item.setAttribute("data-index", index);
      item.setAttribute("data-indent", getItemDepth(item));
    });
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
        <li class="relay-menu-item" data-index="${index}" data-indent="0">
          <div class="relay-menu-item-row">
                <div class="relay-menu-item-controls">
            <span class="relay-button-icon relay-drag-handle" role="img" aria-label="Drag to reorder menu item" title="Drag to reorder">&#8942;&#8942;</span>
            <button type="button" class="relay-button-icon move-up" aria-label="Move menu item up" title="Move Up">↑</button>
            <button type="button" class="relay-button-icon move-down" aria-label="Move menu item down" title="Move Down">↓</button>
            <button type="button" class="relay-button-icon indent-out" aria-label="Outdent menu item" title="Outdent">←</button>
            <button type="button" class="relay-button-icon indent-in" aria-label="Indent menu item" title="Indent">→</button>
                </div>
          <div class="relay-menu-item-content">
            <input type="text" class="menu-item-label" value="" placeholder="Label" aria-label="Menu item label">
            <input type="text" class="menu-item-url" value="" placeholder="URL" aria-label="Menu item URL">
            <button type="button" class="relay-button relay-button-danger delete-item" aria-label="Delete menu item">Delete</button>
                </div>
          </div>
        </li>
        `;

    container.insertAdjacentHTML("beforeend", itemHtml);
    updateIndices();
    updateControlStates();
    refreshSortables();
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
      const parentList = item.parentElement;
      item.remove();
      removeEmptyChildList(parentList);
      updateIndices();
      updateControlStates();
      refreshSortables();
      markUnsaved();

      // Show empty message if no items left
      const items = getMenuItems();
      if (items.length === 0) {
        const container = document.getElementById("menu-items");
        container.innerHTML =
          '<li class="relay-menu-empty">No menu items. Click "Add Item" to create one.</li>';
      }
    }
  }

  // Move item up
  function moveItemUp(item, control) {
    const prev = item.previousElementSibling;
    if (prev && prev.classList.contains("relay-menu-item")) {
      item.parentNode.insertBefore(item, prev);
      completeMovement(control);
    }
  }

  // Move item down
  function moveItemDown(item, control) {
    const next = item.nextElementSibling;
    if (next && next.classList.contains("relay-menu-item")) {
      item.parentNode.insertBefore(next, item);
      completeMovement(control);
    }
  }

  // Indent item (increase nesting level)
  function indentItem(item, control) {
    const previousItem = item.previousElementSibling;

    if (previousItem && previousItem.classList.contains("relay-menu-item")) {
      getOrCreateChildList(previousItem).appendChild(item);
      completeMovement(control);
    }
  }

  // Outdent item (decrease nesting level)
  function outdentItem(item, control) {
    const parentList = item.parentElement;

    if (parentList && parentList.classList.contains("relay-menu-children")) {
      const parentItem = parentList.parentElement;
      parentItem.parentElement.insertBefore(item, parentItem.nextElementSibling);
      removeEmptyChildList(parentList);
      completeMovement(control);
    }
  }

  // Recursively collect a sibling list into the persisted nested structure
  function collectListData(list) {
    const collectedItems = [];

    getDirectItems(list).forEach((item) => {
      const row = item.querySelector(":scope > .relay-menu-item-row");
      const label = row.querySelector(".menu-item-label").value.trim();
      const url = row.querySelector(".menu-item-url").value.trim();
      const childList = getChildList(item);
      const children = childList ? collectListData(childList) : [];

      if (label && url) {
        const menuItem = { label, url };

        if (children.length > 0) {
          menuItem.children = children;
        }

        collectedItems.push(menuItem);
      } else {
        collectedItems.push(...children);
      }
    });

    return collectedItems;
  }

  // Collect menu data from the nested editor DOM
  function collectMenuData() {
    return collectListData(document.getElementById("menu-items"));
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
      moveItemUp(item, target);
    } else if (target.classList.contains("move-down")) {
      moveItemDown(item, target);
    } else if (target.classList.contains("indent-in")) {
      indentItem(item, target);
    } else if (target.classList.contains("indent-out")) {
      outdentItem(item, target);
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

  updateIndices();
  updateControlStates();
  refreshSortables();
})();
