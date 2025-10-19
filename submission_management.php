<?php
session_start();
// Check if user is student
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: signup.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Plagiarism Detection Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
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
        width: 80px;
        background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%);
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 20px 0;
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        overflow: hidden;
        z-index: 1000;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    }

    .sidebar a {
        color: white;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 18px 0;
        margin: 10px;
        border-radius: 12px;
        transition: all 0.3s;
        cursor: pointer;
        font-size: 20px;
        position: relative;
    }

    .sidebar a:hover, .sidebar a.active {
        background-color: rgba(59, 130, 246, 0.5);
        transform: translateX(5px);
    }

    .sidebar .logout {
        background-color: rgba(239, 68, 68, 0.8);
        margin: 10px;
    }

    .sidebar .logout:hover {
        background-color: #ef4444;
    }

    /* ===== Tooltip ===== */
    .sidebar a::before {
        content: attr(data-tooltip);
        position: absolute;
        left: 90px;
        background: #1e3a8a;
        color: white;
        padding: 8px 12px;
        border-radius: 8px;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s;
        font-size: 13px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }

    .sidebar a:hover::before {
        opacity: 1;
    }

    /* ===== Main Content ===== */
    .main-content {
        flex: 1;
        margin-left: 80px;
        padding: 40px;
        background: #f9fafc;
        transition: margin-left 0.3s;
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

    select, input[type="file"] {
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

    /* ===== File Warning ===== */
    .file-warning {
        display: none;
        margin-top: 10px;
        padding: 12px;
        background: rgba(255, 0, 0, 0.1);
        color: #b91c1c;
        border-radius: 8px;
        font-weight: 500;
        font-size: 14px;
        backdrop-filter: blur(5px);
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ===== Results Box ===== */
    .results-box {
        background-color: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        text-align: center;
        transition: all 0.4s;
    }

    .results-box.active {
        border-top: 5px solid #2563eb;
        transform: translateY(-3px);
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

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .percent-breakdown {
        text-align: left;
        margin-top: 10px;
        padding: 0 20px;
        font-size: 15px;
    }

    .indicator-box {
        display: inline-block;
        width: 12px;
        height: 12px;
        margin-right: 8px;
        border-radius: 3px;
    }

    .unique-box { background-color: #22c55e; }
    .exact-box { background-color: #ef4444; }
    .partial-box { background-color: #facc15; }

    /* ===== Report Section ===== */
    .report-section {
        margin-top: 20px;
        padding: 15px;
        border-top: 1px solid #e2e8f0;
        display: none;
    }

    .report-section.active {
        display: block;
    }

    .report-section p {
        margin-bottom: 10px;
    }

    .download-btn {
        margin-top: 10px;
        background-color: #22c55e;
    }

    /* ===== Chat Box ===== */
    .chat-box {
        position: fixed;
        bottom: -400px;
        right: 30px;
        width: 300px;
        height: 400px;
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

    .chat-open { bottom: 30px; }

    .page { display: none; }
    .page.active { display: block; }
    /* ===== Timeline ===== */
.timeline {
  position: relative;
  margin: 30px 0;
  padding-left: 30px;
  border-left: 4px solid #1e3a8a;
}

.timeline-item {
  position: relative;
  margin-bottom: 30px;
}

.timeline-dot {
  position: absolute;
  left: -11px;
  top: 5px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 3px solid #f9fafc;
  box-shadow: 0 0 0 3px #1e3a8a;
}

.timeline-dot.low {
  background-color: #22c55e; /* green */
}

.timeline-dot.medium {
  background-color: #facc15; /* yellow */
}

.timeline-dot.high {
  background-color: #ef4444; /* red */
}

.timeline-content {
  background-color: white;
  padding: 15px 20px;
  border-radius: 10px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.08);
  transition: transform 0.2s, box-shadow 0.2s;
}

.timeline-content:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 15px rgba(0,0,0,0.15);
}

.timeline-content h3 {
  color: #1e3a8a;
  font-size: 18px;
  margin-bottom: 6px;
}

.timeline-content p {
  margin-bottom: 4px;
  font-size: 14px;
  color: #334155;
}
/* ===== Timeline (History Page) ===== */
.timeline {
  position: relative;
  margin: 30px 0;
  padding-left: 30px;
  border-left: 4px solid #1e3a8a;
}

.timeline-item {
  position: relative;
  margin-bottom: 30px;
}

.timeline-dot {
  position: absolute;
  left: -11px;
  top: 5px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 3px solid #f9fafc;
  box-shadow: 0 0 0 3px #1e3a8a;
}

.timeline-dot.low {
  background-color: #22c55e; /* green */
}

.timeline-dot.medium {
  background-color: #facc15; /* yellow */
}

.timeline-dot.high {
  background-color: #ef4444; /* red */
}

.timeline-content {
  background-color: white;
  padding: 15px 20px;
  border-radius: 10px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.08);
  transition: transform 0.2s, box-shadow 0.2s;
}

.timeline-content:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 15px rgba(0,0,0,0.15);
}

.timeline-content h3 {
  color: #1e3a8a;
  font-size: 18px;
  margin-bottom: 6px;
}

.timeline-content p {
  margin-bottom: 4px;
  font-size: 14px;
  color: #334155;
}

/* ===== Trash Page ===== */
.trash-subtext {
  color: #475569;
  font-size: 15px;
  margin-bottom: 20px;
}

.trash-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}

.trash-card {
  background-color: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.08);
  transition: transform 0.2s, box-shadow 0.2s;
}

.trash-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 15px rgba(0,0,0,0.15);
}

.trash-card h3 {
  color: #1e3a8a;
  margin-bottom: 8px;
  font-size: 17px;
}

.trash-status {
  color: #dc2626;
  font-weight: 500;
  margin-bottom: 10px;
}

.restore-btn {
  background-color: #1e3a8a;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 8px 14px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.3s ease;
}

.restore-btn:hover {
  background-color: #3347c7;
}


</style>
</head>
<body>

<div class="sidebar">
    <div>
        <a id="homeBtn" data-tooltip="Home"><i class="fas fa-home"></i></a>
        <a id="chatToggle" data-tooltip="Chat"><i class="fas fa-comments"></i></a>
        <a id="historyBtn" data-tooltip="Past History"><i class="fas fa-history"></i></a>
        <a id="trashBtn" data-tooltip="Trash"><i class="fas fa-trash"></i></a>
    </div>
   <a href="logout.php" class="logout" data-tooltip="Logout"><i class="fas fa-sign-out-alt"></i></a>
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

               <div class="file-upload-box">
  <label for="fileInput">Upload File (Max 10 MB)
    PDF,DOCX, DOC, TXT only
  </label>
  <input type="file" id="fileInput" accept=".pdf, .doc, .docx, .txt">
  <div class="file-warning" id="fileWarning"></div>
</div>

                <button id="submitBtn" onclick="checkPlagiarism()">Submit</button>
            </div>

            <div class="results-box" id="resultsBox">
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
                <div class="report-section" id="reportSection">
                    <p><strong>Generated Report:</strong> Your document has been analyzed. A downloadable report is available below.</p>
                    <button class="download-btn" onclick="downloadReport()">Download Report</button>
                </div>
            </div>
        </div>
    </div>

   <!-- Past History Page (Timeline Style) -->
<div id="historyPage" class="page">
  <h1>📜 Submission History</h1>
  <div class="timeline">
    <div class="timeline-item">
      <div class="timeline-dot low"></div>
      <div class="timeline-content">
        <h3>Essay on Climate Change</h3>
        <p><strong>Date:</strong> Oct 10, 2025</p>
        <p><strong>Course:</strong> Environmental Science</p>
        <p><strong>Similarity:</strong> 18%</p>
        <p><strong>Severity:</strong> Low</p>
      </div>
    </div>

    <div class="timeline-item">
      <div class="timeline-dot medium"></div>
      <div class="timeline-content">
        <h3>AI Ethics Report</h3>
        <p><strong>Date:</strong> Oct 14, 2025</p>
        <p><strong>Course:</strong> Computer Science</p>
        <p><strong>Similarity:</strong> 42%</p>
        <p><strong>Severity:</strong> Medium</p>
      </div>
    </div>

    <div class="timeline-item">
      <div class="timeline-dot high"></div>
      <div class="timeline-content">
        <h3>Literature Review on Psychology</h3>
        <p><strong>Date:</strong> Oct 17, 2025</p>
        <p><strong>Course:</strong> Psychology 101</p>
        <p><strong>Similarity:</strong> 75%</p>
        <p><strong>Severity:</strong> High</p>
      </div>
    </div>
  </div>
</div>

   <!-- ===== Trash Page (Stylish Deleted Submissions) ===== -->
<div id="trashPage" class="page">
  <h1>🗑️ Trash Bin</h1>
  <p class="trash-subtext">Deleted submissions are kept here temporarily before permanent removal.</p>

  <div class="trash-container">
    <div class="trash-card">
      <h3>Old Research Draft</h3>
      <p><strong>Removed on:</strong> Oct 8, 2025</p>
      <p class="trash-status">Status: Pending Deletion</p>
      <button class="restore-btn">Restore</button>
    </div>

    <div class="trash-card">
      <h3>Group Report on AI</h3>
      <p><strong>Removed on:</strong> Oct 12, 2025</p>
      <p class="trash-status">Status: Pending Deletion</p>
      <button class="restore-btn">Restore</button>
    </div>

    <div class="trash-card">
      <h3>Essay on Renewable Energy</h3>
      <p><strong>Removed on:</strong> Oct 15, 2025</p>
      <p class="trash-status">Status: Pending Deletion</p>
      <button class="restore-btn">Restore</button>
    </div>
  </div>
</div>

<!-- Chat Box -->
<div class="chat-box" id="chatBox">
    <div class="chat-header">
        Student Chat
        <span style="cursor:pointer;" onclick="toggleChat()">✖</span>
    </div>
    <div class="chat-messages" id="chatMessages">
        <p><strong>Teacher:</strong> Hey, how can I help you?</p>
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

    const resultsBox = document.getElementById("resultsBox");
    const reportSection = document.getElementById("reportSection");
    const submitBtn = document.getElementById("submitBtn");

    resultsBox.classList.add("active");
    reportSection.classList.add("active");
    submitBtn.textContent = "Resubmit";
}

function downloadReport() {
    const report = `
Plagiarism Detection Report
---------------------------
Plagiarised: ${document.getElementById("ringValue").textContent}
Unique: ${document.getElementById("uniqueValue").textContent}
Exact Match: ${document.getElementById("exactValue").textContent}
Partial Match: ${document.getElementById("partialValue").textContent}

Generated on: ${new Date().toLocaleString()}
    `;
    // Ask user which format they want
    const format = (prompt("Choose download format: pdf, docx, doc, txt", "pdf") || "").toLowerCase();
    const mimeMap = {
      pdf: "application/pdf",
      docx: "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
      doc: "application/msword",
      txt: "text/plain"
    };

    if (!mimeMap[format]) {
      alert("Invalid format selected. Download cancelled.");
      return;
    }

   
    const blob = new Blob([report], { type: mimeMap[format] });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "Plagiarism_Report." + format;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
    
}

// ===== File Validation =====
document.getElementById('fileInput').addEventListener('change', function() {
  const file = this.files[0];
  const warning = document.getElementById('fileWarning');
  const maxSize = 10 * 1024 * 1024; // 10 MB in bytes
  const allowedExtensions = ['pdf', 'doc', 'docx', 'txt'];

  if (!file) {
    warning.style.display = 'none';
    return;
  }

  const fileExtension = file.name.split('.').pop().toLowerCase();

  // Check file type
  if (!allowedExtensions.includes(fileExtension)) {
    warning.textContent = 'File format not supported. Please upload documents in PDF, DOC, DOCX, or TXT format only.';
    warning.style.display = 'block';
    this.value = '';
    return;
  }

  // Check file size
  if (file.size > maxSize) {
    warning.textContent = 'File too large! Please upload a file smaller than 10 MB.';
    warning.style.display = 'block';
    this.value = '';
    return;
  }

  // All good
  warning.style.display = 'none';
});
// ===== Chat Toggle =====
const chatBox = document.getElementById("chatBox");
const chatToggle = document.getElementById("chatToggle");
chatToggle.addEventListener("click", toggleChat);

function toggleChat() {
    chatBox.classList.toggle("chat-open");
}

// ===== Chat =====
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

// ===== Teacher Dropdown =====
document.getElementById("submissionType").addEventListener("change", function() {
    const teacherDropdown = document.getElementById("teacherDropdown");
    teacherDropdown.style.display = (this.value === "specific") ? "block" : "none";
});
</script>

</body>
</html>
