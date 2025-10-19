<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Plagiarism Detection Dashboard</title>
<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: "Poppins", sans-serif;
    }

    body {
        display: flex;
        background-color: #f4f8ff;
        min-height: 100vh;
    }

    /* ===== Sidebar ===== */
    .sidebar {
        width: 230px;
        background: #1e3a8a;
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 20px 15px;
    }

    .sidebar h2 {
        text-align: center;
        margin-bottom: 30px;
        font-weight: 600;
    }

    .sidebar a {
        color: white;
        text-decoration: none;
        display: block;
        padding: 12px;
        margin: 8px 0;
        border-radius: 8px;
        transition: 0.3s;
        cursor: pointer;
        font-size: 15px;
    }

    .sidebar a:hover, .sidebar a.active {
        background-color: #3b82f6;
    }

    .sidebar .logout {
        background-color: #ef4444;
        text-align: center;
    }

    /* ===== Main Content ===== */
    .main-content {
        flex: 1;
        padding: 40px;
        position: relative;
        background: #f9fafc;
    }

    h1 {
        color: #1e3a8a;
        margin-bottom: 25px;
        font-size: 28px;
    }

    .content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }

    /* ===== Submission Box ===== */
    .submission-box {
        background-color: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    .submission-box h2 {
        color: #1e3a8a;
        margin-bottom: 20px;
    }

    textarea {
        width: 100%;
        height: 150px;
        padding: 12px;
        margin-bottom: 15px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        resize: none;
    }

    input[type="file"], select {
        width: 100%;
        margin-bottom: 15px;
        padding: 8px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
    }

    button {
        background-color: #2563eb;
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 15px;
        transition: 0.3s;
    }

    button:hover {
        background-color: #1e40af;
    }

    /* ===== Results Box ===== */
    .results-box {
        background-color: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        text-align: center;
    }

    .ring-container {
        position: relative;
        width: 150px;
        height: 150px;
        margin: 0 auto 20px;
    }

    .ring {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        border: 12px solid #e2e8f0;
        border-top-color: #2563eb;
        animation: spin 2s linear infinite;
    }

    .ring-value {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 24px;
        color: #1e3a8a;
        font-weight: 600;
    }

    .percent-breakdown {
        text-align: left;
        margin-top: 20px;
    }

    .percent-breakdown div {
        margin: 6px 0;
        display: flex;
        align-items: center;
    }

    .indicator-box {
        width: 15px;
        height: 15px;
        border-radius: 3px;
        margin-right: 8px;
    }

    .unique-box { background-color: #22c55e; }
    .exact-box { background-color: #ef4444; }
    .partial-box { background-color: #facc15; }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* ===== Chat Box ===== */
    .chat-box {
        position: fixed;
        bottom: -400px;
        right: 30px;
        width: 300px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        transition: bottom 0.4s ease;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        z-index: 100;
    }

    .chat-header {
        background: #1e3a8a;
        color: white;
        padding: 12px;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chat-messages {
        flex: 1;
        padding: 12px;
        overflow-y: auto;
        font-size: 14px;
        color: #333;
    }

    .chat-input {
        display: flex;
        border-top: 1px solid #ccc;
    }

    .chat-input input {
        flex: 1;
        border: none;
        padding: 10px;
    }

    .chat-input button {
        background: #2563eb;
        color: white;
        border: none;
        padding: 10px 14px;
        cursor: pointer;
    }

    .chat-input button:hover {
        background: #1e40af;
    }

    .chat-open {
        bottom: 30px;
    }

    /* ===== Page Sections ===== */
    .page {
        display: none;
    }

    .page.active {
        display: block;
    }

    .history-item, .trash-item {
        background: white;
        border-left: 4px solid #2563eb;
        margin-bottom: 10px;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .history-item h3, .trash-item h3 {
        color: #1e3a8a;
        font-size: 16px;
        margin-bottom: 6px;
    }

    .history-item p, .trash-item p {
        color: #475569;
        font-size: 14px;
    }

    @media (max-width: 900px) {
        .content-grid {
            grid-template-columns: 1fr;
        }
        .sidebar {
            width: 180px;
        }
    }
</style>
</head>
<body>

    <div class="sidebar">
        <div>
            <h2>Student</h2>
            <a id="homeBtn">🏠 Home</a>
            <a id="chatToggle">💬 Chat</a>
            <a id="historyBtn">📜 Past History</a>
            <a id="trashBtn">🗑️ Trash</a>
        </div>
        <a href="#" class="logout">Logout</a>
    </div>

    <div class="main-content">
        <!-- Submission Page -->
        <div id="mainPage" class="page active">
            <h1>Plagiarism Detection</h1>
            <div class="content-grid">
                <div class="submission-box">
                    <h2>Submit Your Work</h2>
                    <select id="submissionType">
                        <option value="general">General Submission</option>
                        <option value="specific">Specific Teacher</option>
                    </select>

                    <div id="teacherDropdown" style="display:none;">
                        <select id="teacherSelect">
                            <option value="">-- Select a Teacher --</option>
                            <option value="Mr. Ahmed">Mr. Ahmed</option>
                            <option value="Ms. Fatma">Ms. Fatma</option>
                            <option value="Dr. Khaled">Dr. Khaled</option>
                        </select>
                    </div>

                    <textarea id="textInput" placeholder="Enter your text here..."></textarea>
                    <input type="file" id="fileInput" accept=".pdf, .doc, .docx">
                    <button onclick="checkPlagiarism()">Submit</button>
                </div>

                <div class="results-box">
                    <h2>Results</h2>
                    <div class="ring-container">
                        <div class="ring"></div>
                        <div class="ring-value" id="ringValue">0%</div>
                    </div>
                    <div class="percent-breakdown">
                        <div><div class="indicator-box unique-box"></div> Unique: <span id="uniqueValue">0%</span></div>
                        <div><div class="indicator-box exact-box"></div> Exact Match: <span id="exactValue">0%</span></div>
                        <div><div class="indicator-box partial-box"></div> Partial Match: <span id="partialValue">0%</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Past History Page -->
        <div id="historyPage" class="page">
            <h1>📜 Past History</h1>
            <div class="history-item"><h3>Submission #1</h3><p>Plagiarism Score: 45%</p></div>
            <div class="history-item"><h3>Submission #2</h3><p>Plagiarism Score: 20%</p></div>
        </div>

        <!-- Trash Page -->
        <div id="trashPage" class="page">
            <h1>🗑️ Trash</h1>
            <div class="trash-item"><h3>Old File - Removed</h3><p>Deleted on: 12 Oct 2025</p></div>
            <div class="trash-item"><h3>Duplicate Submission</h3><p>Deleted on: 10 Oct 2025</p></div>
        </div>
    </div>

    <!-- Chat Box -->
    <div class="chat-box" id="chatBox">
        <div class="chat-header">
            Student Chat
            <span style="cursor:pointer;" onclick="toggleChat()">✖</span>
        </div>
        <div class="chat-messages" id="chatMessages">
            <p><strong>teacher</strong> Hey, how can i help you?</p>
        </div>
        <div class="chat-input">
            <input type="text" id="chatInput" placeholder="Type a message...">
            <button onclick="sendMessage()">Send</button>
        </div>
    </div>

<script>
function checkPlagiarism() {
    const total = 100;
    const plagiarised = Math.floor(Math.random() * 60) + 10;
    const unique = total - plagiarised;
    const exact = Math.floor(Math.random() * (plagiarised / 2));
    const partial = plagiarised - exact;

    document.getElementById("ringValue").textContent = plagiarised + "%";
    document.getElementById("uniqueValue").textContent = unique + "%";
    document.getElementById("exactValue").textContent = exact + "%";
    document.getElementById("partialValue").textContent = partial + "%";
}

// ===== Chat Toggle =====
const chatBox = document.getElementById("chatBox");
const chatToggle = document.getElementById("chatToggle");
chatToggle.addEventListener("click", toggleChat);

function toggleChat() {
    chatBox.classList.toggle("chat-open");
}

// ===== Simple Chat (no AI) =====
function sendMessage() {
    const input = document.getElementById("chatInput");
    const message = input.value.trim();
    if (!message) return;

    const messages = document.getElementById("chatMessages");
    const userMsg = document.createElement("p");
    userMsg.innerHTML = `<strong>You:</strong> ${message}`;
    messages.appendChild(userMsg);

    input.value = "";
    messages.scrollTop = messages.scrollHeight;
}

// ===== Page Navigation =====
const mainPage = document.getElementById("mainPage");
const historyPage = document.getElementById("historyPage");
const trashPage = document.getElementById("trashPage");

document.getElementById("homeBtn").addEventListener("click", () => showPage("home"));
document.getElementById("historyBtn").addEventListener("click", () => showPage("history"));
document.getElementById("trashBtn").addEventListener("click", () => showPage("trash"));

function showPage(page) {
    mainPage.classList.remove("active");
    historyPage.classList.remove("active");
    trashPage.classList.remove("active");

    if (page === "history") historyPage.classList.add("active");
    else if (page === "trash") trashPage.classList.add("active");
    else mainPage.classList.add("active");
}

// ===== Teacher Dropdown Visibility =====
document.getElementById("submissionType").addEventListener("change", function() {
    const teacherDropdown = document.getElementById("teacherDropdown");
    if (this.value === "specific") {
        teacherDropdown.style.display = "block";
    } else {
        teacherDropdown.style.display = "none";
    }
});
</script>

</body>
</html>
