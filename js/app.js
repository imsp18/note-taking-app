document.addEventListener("DOMContentLoaded", function () {
  // DOM elements
  const notesList = document.getElementById("notesList");
  const noteTitle = document.getElementById("noteTitle");
  const noteContent = document.getElementById("noteContent");
  const saveNoteBtn = document.getElementById("saveNoteBtn");
  const deleteNoteBtn = document.getElementById("deleteNoteBtn");
  const newNoteBtn = document.getElementById("newNoteBtn");
  const logoutBtn = document.getElementById("logoutBtn");
  const searchInput = document.getElementById("searchInput");
  const sortBy = document.getElementById("sortBy");
  const saveStatus = document.getElementById("saveStatus");
  const confirmDeleteModal = document.getElementById("confirmDeleteModal");
  const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
  const cancelDeleteBtn = document.getElementById("cancelDeleteBtn");

  // App state
  let currentNoteId = null;
  let notes = [];
  let searchTerm = "";
  let sortOption = "updated_at-DESC";
  let autosaveTimer = null;
  let isDraft = false;

  // Initialize app
  init();

  // Event listeners
  logoutBtn.addEventListener("click", logout);
  newNoteBtn.addEventListener("click", createNewNote);
  saveNoteBtn.addEventListener("click", saveNote);
  deleteNoteBtn.addEventListener("click", showDeleteConfirmation);
  confirmDeleteBtn.addEventListener("click", deleteNote);
  cancelDeleteBtn.addEventListener("click", hideDeleteConfirmation);
  searchInput.addEventListener("input", handleSearch);
  sortBy.addEventListener("change", handleSort);

  // Autosave functionality
  noteTitle.addEventListener("input", scheduleAutosave);
  noteContent.addEventListener("input", scheduleAutosave);

  // Initialize app
  function init() {
    loadNotes();

    // Hide delete button initially
    deleteNoteBtn.style.display = "none";
  }

  // Load notes from server
  function loadNotes() {
    const params = new URLSearchParams();
    if (searchTerm) params.append("search", searchTerm);

    const [sortByField, sortOrder] = sortOption.split("-");
    params.append("sort_by", sortByField);
    params.append("sort_order", sortOrder);

    notesList.innerHTML = '<div class="loading">Loading notes...</div>';

    fetch(`api/notes.php?${params.toString()}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          notes = data.notes;
          renderNotesList();

          // If we have notes and no current note is selected, select the first one
          if (notes.length > 0 && !currentNoteId) {
            selectNote(notes[0].note_id);
          } else if (notes.length === 0) {
            clearNoteEditor();
            notesList.innerHTML =
              '<div class="empty-state">No notes found. Create a new one!</div>';
          }
        } else {
          notesList.innerHTML =
            '<div class="empty-state">Failed to load notes.</div>';
        }
      })
      .catch((error) => {
        console.error("Error loading notes:", error);
        notesList.innerHTML =
          '<div class="empty-state">Error loading notes. Please try again.</div>';
      });
  }

  // Render notes list
  function renderNotesList() {
    if (notes.length === 0) {
      notesList.innerHTML =
        '<div class="empty-state">No notes found. Create a new one!</div>';
      return;
    }

    let html = "";
    notes.forEach((note) => {
      const isActive = currentNoteId === note.note_id ? "active" : "";
      const isDraftClass = note.is_draft === "1" ? "draft" : "";
      const updatedDate = new Date(note.updated_at).toLocaleString();

      html += `
                <div class="note-item ${isActive} ${isDraftClass}" data-id="${
        note.note_id
      }">
                    <h3>${note.title || "Untitled Note"}</h3>
                    <p>${note.content.substring(0, 50)}${
        note.content.length > 50 ? "..." : ""
      }</p>
                    <span class="note-date">Last updated: ${updatedDate}</span>
                </div>
            `;
    });

    notesList.innerHTML = html;

    // Add click event to each note item
    document.querySelectorAll(".note-item").forEach((item) => {
      item.addEventListener("click", () => selectNote(item.dataset.id));
    });
  }

  // Select a note
  function selectNote(noteId) {
    // Clear autosave timer for previous note
    if (autosaveTimer) {
      clearTimeout(autosaveTimer);
      autosaveTimer = null;
    }

    currentNoteId = parseInt(noteId);

    // Update active class in notes list
    document.querySelectorAll(".note-item").forEach((item) => {
      if (item.dataset.id == noteId) {
        item.classList.add("active");
      } else {
        item.classList.remove("active");
      }
    });

    // Find the note in our local notes array
    const note = notes.find((n) => n.note_id == noteId);
    if (note) {
      noteTitle.value = note.title || "";
      noteContent.value = note.content || "";
      isDraft = note.is_draft === "1";

      // Show delete button for existing notes
      deleteNoteBtn.style.display = "inline-block";

      // Update save status
      saveStatus.textContent = isDraft ? "Draft" : "Saved";
    }
  }

  // Create a new note
  function createNewNote() {
    // Clear editor
    clearNoteEditor();

    // Create a new draft note on the server
    const noteData = {
      title: "",
      content: "",
      is_draft: true,
    };

    fetch("api/notes.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(noteData),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          currentNoteId = data.note.note_id;
          isDraft = true;

          // Add the new note to our notes array
          notes.unshift(data.note);

          // Reload notes list
          loadNotes();

          // Focus on title field
          noteTitle.focus();

          // Show delete button
          deleteNoteBtn.style.display = "inline-block";

          // Update save status
          saveStatus.textContent = "Draft";
        }
      })
      .catch((error) => {
        console.error("Error creating note:", error);
        alert("Failed to create new note. Please try again.");
      });
  }

  // Save the current note
  function saveNote() {
    if (!currentNoteId) return;

    const noteData = {
      title: noteTitle.value.trim(),
      content: noteContent.value,
      is_draft: false,
    };

    // Validate title
    if (!noteData.title) {
      alert("Please enter a title for your note");
      noteTitle.focus();
      return;
    }

    // Update on server
    fetch(`api/notes.php`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        note_id: currentNoteId,
        ...noteData,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Update local notes array
          const index = notes.findIndex((n) => n.note_id == currentNoteId);
          if (index !== -1) {
            notes[index] = data.note;
          }

          isDraft = false;

          // Update UI
          renderNotesList();

          // Update save status
          saveStatus.textContent = "Saved";

          // Show toast notification
          showToast("Note saved successfully");
        }
      })
      .catch((error) => {
        console.error("Error saving note:", error);
        alert("Failed to save note. Please try again.");
      });
  }

  // Delete the current note
  function deleteNote() {
    if (!currentNoteId) return;

    fetch(`api/notes.php`, {
      method: "DELETE",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        note_id: currentNoteId,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Remove from local notes array
          notes = notes.filter((n) => n.note_id != currentNoteId);

          // Reset current note
          currentNoteId = null;

          // Hide delete confirmation modal
          hideDeleteConfirmation();

          // Reload notes list
          loadNotes();

          // Clear editor
          clearNoteEditor();

          // Show toast notification
          showToast("Note deleted successfully");
        }
      })
      .catch((error) => {
        console.error("Error deleting note:", error);
        alert("Failed to delete note. Please try again.");
        hideDeleteConfirmation();
      });
  }

  // Show delete confirmation modal
  function showDeleteConfirmation() {
    if (!currentNoteId) return;

    confirmDeleteModal.classList.add("active");
  }

  // Hide delete confirmation modal
  function hideDeleteConfirmation() {
    confirmDeleteModal.classList.remove("active");
  }

  // Clear note editor
  function clearNoteEditor() {
    noteTitle.value = "";
    noteContent.value = "";
    currentNoteId = null;
    isDraft = false;

    // Hide delete button for new notes
    deleteNoteBtn.style.display = "none";

    // Clear save status
    saveStatus.textContent = "";
  }

  // Handle search input
  function handleSearch(e) {
    searchTerm = e.target.value.trim();
    loadNotes();
  }

  // Handle sort selection
  function handleSort(e) {
    sortOption = e.target.value;
    loadNotes();
  }

  // Schedule autosave
  function scheduleAutosave() {
    // Update save status
    saveStatus.textContent = "Typing...";

    // Clear previous timer
    if (autosaveTimer) {
      clearTimeout(autosaveTimer);
    }

    // Set new timer
    autosaveTimer = setTimeout(() => {
      autosave();
    }, 2000); // 2 seconds delay
  }

  // Perform autosave
  function autosave() {
    if (!currentNoteId) return;

    const noteData = {
      note_id: currentNoteId,
      title: noteTitle.value.trim() || "Untitled Note",
      content: noteContent.value,
      is_draft: true,
    };

    // Send to server
    fetch("api/notes.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "autosave",
        ...noteData,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Update save status
          saveStatus.textContent = "Draft saved";

          // Update local note if needed
          const index = notes.findIndex((n) => n.note_id == currentNoteId);
          if (index !== -1) {
            notes[index].title = noteData.title;
            notes[index].content = noteData.content;
            notes[index].is_draft = "1";
            renderNotesList();
          }
        }
      })
      .catch((error) => {
        console.error("Error autosaving note:", error);
        saveStatus.textContent = "Autosave failed";
      });
  }

  // Logout function
  function logout() {
    // Form data for logout
    const formData = new FormData();
    formData.append("action", "logout");

    fetch("api/auth.php", {
      method: "POST",
      body: formData,
    })
      .then(() => {
        // Redirect to login page
        window.location.href = "index.php";
      })
      .catch((error) => {
        console.error("Error logging out:", error);
        alert("Failed to logout. Please try again.");
      });
  }

  // Show toast notification
  function showToast(message) {
    // Create toast element if it doesn't exist
    let toast = document.querySelector(".toast");
    if (!toast) {
      toast = document.createElement("div");
      toast.className = "toast";
      document.body.appendChild(toast);

      // Add styles for toast
      const style = document.createElement("style");
      style.textContent = `
                .toast {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background-color: #333;
                    color: #fff;
                    padding: 12px 20px;
                    border-radius: 4px;
                    font-size: 14px;
                    z-index: 1000;
                    opacity: 0;
                    transition: opacity 0.3s;
                }
                .toast.show {
                    opacity: 1;
                }
            `;
      document.head.appendChild(style);
    }

    // Set message and show toast
    toast.textContent = message;
    toast.classList.add("show");

    // Hide toast after 3 seconds
    setTimeout(() => {
      toast.classList.remove("show");
    }, 3000);
  }
});
