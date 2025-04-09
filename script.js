const loginForm = document.getElementById("loginForm");
const registerForm = document.getElementById("registerForm");

if (loginForm) {
  loginForm.addEventListener("submit", function (e) {
    e.preventDefault();
    localStorage.setItem("loggedIn", "true");
    window.location.href = "dashboard.html";
  });
}

if (registerForm) {
  registerForm.addEventListener("submit", function (e) {
    e.preventDefault();
    alert("Registered! Please login.");
    window.location.href = "index.html";
  });
}

function logout() {
  localStorage.removeItem("loggedIn");
  window.location.href = "index.html";
}

if (
  window.location.pathname.includes("dashboard.html") &&
  !localStorage.getItem("loggedIn")
) {
  window.location.href = "index.html";
}

let notes = [];

function addNote() {
  const title = document.getElementById("noteTitle").value;
  const content = document.getElementById("noteContent").value;

  if (!title || !content) return;

  const note = { title, content };
  notes.push(note);
  renderNotes();
  document.getElementById("noteTitle").value = "";
  document.getElementById("noteContent").value = "";
}

function renderNotes() {
  const container = document.getElementById("noteList");
  container.innerHTML = "";
  notes.forEach((n) => {
    const noteEl = document.createElement("div");
    noteEl.className = "note";
    noteEl.innerHTML = `<h3>${n.title}</h3><p>${n.content}</p>`;
    container.appendChild(noteEl);
  });
}
