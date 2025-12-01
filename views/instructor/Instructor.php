

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard - Plagiarism Detector</title>
    <!-- Correct CSS path from root assets folder -->
    <link rel="stylesheet" href="/Plagirism_Detection_System/assets/css/Instructor.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>📚 Similyze</h2>
            <p>Instructor Portal</p>
        </div>
        
        <div class="profile-section">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($instructor['name'] ?? 'IN', 0, 2)); ?>
            </div>
            <div class="profile-info">
                <h3><?php echo htmlspecialchars($instructor['name'] ?? 'Unknown'); ?></h3>
                <p>📧 <?php echo htmlspecialchars($instructor['email'] ?? 'N/A'); ?></p>
                <p>🏫 <?php echo htmlspecialchars($instructor['role'] ?? 'N/A'); ?></p>
                <p>👥 <?php echo $stats['students_enrolled'] ?? 0; ?> Students Enrolled</p>
            </div>
        </div>
        
        <div class="nav-menu">
            <a href="?view=submissions" class="nav-item <?php echo ($current_view ?? 'submissions') === 'submissions' ? 'active' : ''; ?>">
                <span>📋</span> Submissions
            </a>
            <a href="?view=trash" class="nav-item <?php echo ($current_view ?? '') === 'trash' ? 'active' : ''; ?>">
                <span>🗑️</span> Trash
                <?php if (!empty($trash) && count($trash) > 0): ?>
                    <span class="trash-badge"><?php echo count($trash); ?></span>
                <?php endif; ?>
            </a>
        </div>
        
        <div class="students-section">
            <h3>👥 Enrolled Students</h3>
            <?php if (empty($enrolled_students)): ?>
                <p style="padding: 10px; color: #aaa;">No students enrolled yet.</p>
            <?php else: ?>
                <?php foreach ($enrolled_students as $student): ?>
                    <div class="student-item">
                        <h4><?php echo htmlspecialchars($student['name']); ?></h4>
                        <p><?php echo htmlspecialchars($student['id']); ?></p>
                        <p><?php echo htmlspecialchars($student['email']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="signout-section">
            <a href="?signout=true" style="text-decoration: none;">
                <button class="btn-signout">🚪 Sign Out</button>
            </a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="container">
            <div class="header">
                <h1>📚 Instructor Dashboard</h1>
                <p>Review and manage student submissions</p>
            </div>
            
            <div class="stats">
                <div class="stat-card">
                    <h3>Total Submissions</h3>
                    <p><?php echo count($submissions ?? []); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Pending Review</h3>
                    <p><?php echo count(array_filter($submissions ?? [], fn($s) => $s['status'] === 'pending')); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Accepted</h3>
                    <p><?php echo count(array_filter($submissions ?? [], fn($s) => $s['status'] === 'accepted')); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Rejected</h3>
                    <p><?php echo count(array_filter($submissions ?? [], fn($s) => $s['status'] === 'rejected')); ?></p>
                </div>
            </div>
            
            <div class="submissions">
                <?php if (($current_view ?? 'submissions') === 'trash'): ?>
                    <h2>🗑️ Trash</h2>
                    <?php if (empty($trash)): ?>
                        <div class="no-submissions">
                            <h3>Trash is empty</h3>
                            <p>Deleted submissions will appear here.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($trash as $submission): ?>
                            <div class="submission-card">
                                <div class="submission-header">
                                    <div class="student-info">
                                        <h3>
                                            <?php echo htmlspecialchars($submission['student_name']); ?>
                                            <span class="status-badge status-rejected">🗑️ Deleted</span>
                                        </h3>
                                        <p>📧 <?php echo htmlspecialchars($submission['student_email']); ?> | 📅 <?php echo date('F j, Y g:i A', strtotime($submission['created_at'] ?? 'now')); ?></p>
                                    </div>
                                    <div class="plagiarism-badge plagiarism-medium">
                                        <?php echo $submission['similarity'] ?? 0; ?>% Plagiarism
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <h2>Student Submissions</h2>
                    <?php if (empty($submissions)): ?>
                        <div class="no-submissions">
                            <h3>No submissions yet</h3>
                            <p>Student submissions will appear here when they upload their work.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($submissions as $submission): ?>
                            <div class="submission-card">
                                <div class="submission-header">
                                    <div class="student-info">
                                        <h3>
                                            <?php echo htmlspecialchars($submission['student_name']); ?>
                                            <span class="status-badge">
                                                <?php 
                                                echo match($submission['status'] ?? '') {
                                                    'accepted' => '✓ Accepted',
                                                    'rejected' => '✗ Rejected',
                                                    default => '⏳ Pending'
                                                };
                                                ?>
                                            </span>
                                        </h3>
                                        <p>📧 <?php echo htmlspecialchars($submission['student_email']); ?> | 📅 <?php echo date('F j, Y g:i A', strtotime($submission['created_at'] ?? 'now')); ?></p>
                                    </div>
                                    <div class="plagiarism-badge plagiarism-medium">
                                        <?php echo $submission['similarity'] ?? 0; ?>% Plagiarism
                                    </div>
                                </div>
                                <div class="submission-content">
                                    <h4>📄 Document Title: <?php echo htmlspecialchars($submission['stored_name']); ?></h4>
                                    <p><?php echo nl2br(htmlspecialchars(substr($submission['text_content'] ?? '', 0, 300))); ?><?php echo strlen($submission['text_content'] ?? '') > 300 ? '...' : ''; ?></p>
                                </div>
                            </div>
                            <form method="POST" style="display:inline;">
                              <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                              <input type="hidden" name="action" value="trash">
                              <button type="submit" class="btn btn-trash" onclick="return confirm('Move this submission to trash?')">
                             🗑️ Trash
                             </button>
                              </form>

                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
