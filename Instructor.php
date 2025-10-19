<?php
session_start();

// Initialize instructor info
if (!isset($_SESSION['instructor'])) {
    $_SESSION['instructor'] = [
        'name' => 'Dr. Mohamed Ahmed',
        'email' => 'mohamed.ahmed@university.edu',
        'department' => 'Computer Science',
        'students_count' => 24
    ];
}

// Initialize enrolled students
if (!isset($_SESSION['enrolled_students'])) {
    $_SESSION['enrolled_students'] = [
        ['name' => 'Ahmed Hassan', 'email' => 'ahmed.hassan@student.edu', 'student_id' => 'CS2021001'],
        ['name' => 'Sara Mohamed', 'email' => 'sara.mohamed@student.edu', 'student_id' => 'CS2021002'],
        ['name' => 'Omar Khalil', 'email' => 'omar.khalil@student.edu', 'student_id' => 'CS2021003'],
        ['name' => 'Fatima Ali', 'email' => 'fatima.ali@student.edu', 'student_id' => 'CS2021004'],
        ['name' => 'Karim Essam', 'email' => 'karim.essam@student.edu', 'student_id' => 'CS2021005'],
        ['name' => 'Laila Ibrahim', 'email' => 'laila.ibrahim@student.edu', 'student_id' => 'CS2021006'],
        ['name' => 'Youssef Tarek', 'email' => 'youssef.tarek@student.edu', 'student_id' => 'CS2021007'],
        ['name' => 'Nour Hassan', 'email' => 'nour.hassan@student.edu', 'student_id' => 'CS2021008']
    ];
}

// Initialize trash in session if not exists
if (!isset($_SESSION['trash'])) {
    $_SESSION['trash'] = [];
}

// Initialize submissions in session if not exists
if (!isset($_SESSION['submissions'])) {
    $_SESSION['submissions'] = [
        [
            'id' => 1,
            'student_name' => 'Ahmed Hassan',
            'student_email' => 'ahmed.hassan@student.edu',
            'document_title' => 'Research Paper on Artificial Intelligence',
            'content' => 'Artificial Intelligence (AI) has revolutionized the way we interact with technology. Machine learning algorithms have enabled computers to learn from data and make predictions. Deep learning, a subset of machine learning, has shown remarkable results in image recognition and natural language processing. The applications of AI span across various industries including healthcare, finance, and transportation.',
            'plagiarism_percentage' => 15,
            'status' => 'pending',
            'feedback' => '',
            'submission_date' => '2024-10-18 14:30:00'
        ],
        [
            'id' => 2,
            'student_name' => 'Sara Mohamed',
            'student_email' => 'sara.mohamed@student.edu',
            'document_title' => 'Essay on Climate Change',
            'content' => 'Climate change is one of the most pressing issues facing our planet today. The increase in global temperatures has led to melting ice caps, rising sea levels, and extreme weather events. Scientists agree that human activities, particularly the burning of fossil fuels, are the primary cause of recent climate change. Urgent action is needed to reduce greenhouse gas emissions.',
            'plagiarism_percentage' => 65,
            'status' => 'pending',
            'feedback' => '',
            'submission_date' => '2024-10-18 10:15:00'
        ],
        [
            'id' => 3,
            'student_name' => 'Omar Khalil',
            'student_email' => 'omar.khalil@student.edu',
            'document_title' => 'Report on Renewable Energy',
            'content' => 'Renewable energy sources such as solar, wind, and hydroelectric power offer sustainable alternatives to fossil fuels. Solar panels convert sunlight into electricity, while wind turbines harness wind energy. These technologies have become increasingly cost-effective and efficient. Governments worldwide are investing in renewable energy infrastructure to combat climate change and ensure energy security.',
            'plagiarism_percentage' => 35,
            'status' => 'accepted',
            'feedback' => 'Good work overall! The content is well-structured.',
            'submission_date' => '2024-10-17 16:45:00'
        ],
        [
            'id' => 4,
            'student_name' => 'Fatima Ali',
            'student_email' => 'fatima.ali@student.edu',
            'document_title' => 'Analysis of Social Media Impact',
            'content' => 'Social media platforms have transformed communication in the digital age. They enable instant connection with people worldwide and have become essential tools for businesses and individuals. However, concerns about privacy, misinformation, and mental health effects have emerged. Studies show that excessive social media use can lead to anxiety and depression, particularly among young people.',
            'plagiarism_percentage' => 8,
            'status' => 'pending',
            'feedback' => '',
            'submission_date' => '2024-10-19 09:20:00'
        ],
        [
            'id' => 5,
            'student_name' => 'Karim Essam',
            'student_email' => 'karim.essam@student.edu',
            'document_title' => 'Study on Cybersecurity',
            'content' => 'Cybersecurity is critical in protecting digital assets from unauthorized access and attacks. Common threats include malware, phishing, and ransomware. Organizations must implement robust security measures such as firewalls, encryption, and multi-factor authentication. Employee training is also essential as human error is often the weakest link in security systems.',
            'plagiarism_percentage' => 78,
            'status' => 'rejected',
            'feedback' => 'This work shows high plagiarism. Please rewrite in your own words.',
            'submission_date' => '2024-10-16 13:00:00'
        ]
    ];
}

// Handle sign out
if (isset($_GET['signout'])) {
    session_destroy();
    header("Location: logout.php"); // Redirect to login page
    exit();
}

// Handle actions (Accept, Reject, Delete, Feedback, Restore)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = $_POST['submission_id'];
    $action = $_POST['action'];
    
    if ($action === 'restore') {
        // Restore from trash
        foreach ($_SESSION['trash'] as $key => $submission) {
            if ($submission['id'] == $submission_id) {
                $_SESSION['submissions'][] = $submission;
                unset($_SESSION['trash'][$key]);
                $_SESSION['trash'] = array_values($_SESSION['trash']);
                break;
            }
        }
    } elseif ($action === 'delete_permanent') {
        // Permanently delete from trash
        foreach ($_SESSION['trash'] as $key => $submission) {
            if ($submission['id'] == $submission_id) {
                unset($_SESSION['trash'][$key]);
                $_SESSION['trash'] = array_values($_SESSION['trash']);
                break;
            }
        }
    } else {
        // Handle regular submission actions
        foreach ($_SESSION['submissions'] as $key => $submission) {
            if ($submission['id'] == $submission_id) {
                if ($action === 'accept') {
                    $_SESSION['submissions'][$key]['status'] = 'accepted';
                    if (empty($_SESSION['submissions'][$key]['feedback'])) {
                        $_SESSION['submissions'][$key]['feedback'] = 'Work accepted by instructor';
                    }
                } elseif ($action === 'reject') {
                    $_SESSION['submissions'][$key]['status'] = 'rejected';
                    if (empty($_SESSION['submissions'][$key]['feedback'])) {
                        $_SESSION['submissions'][$key]['feedback'] = 'Work rejected due to high plagiarism';
                    }
                } elseif ($action === 'delete') {
                    // Move to trash instead of deleting
                    $_SESSION['trash'][] = $submission;
                    unset($_SESSION['submissions'][$key]);
                    $_SESSION['submissions'] = array_values($_SESSION['submissions']); // Re-index array
                } elseif ($action === 'feedback') {
                    $_SESSION['submissions'][$key]['feedback'] = $_POST['feedback_text'];
                }
                break;
            }
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['view']) ? '?view=' . $_GET['view'] : ''));
    exit();
}

$submissions = $_SESSION['submissions'];
$trash = $_SESSION['trash'];
$instructor = $_SESSION['instructor'];
$enrolled_students = $_SESSION['enrolled_students'];
$current_view = isset($_GET['view']) ? $_GET['view'] : 'submissions';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard - Plagiarism Detector</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
            min-height: 100vh;
            display: flex;
        }
        
        .sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            overflow-y: hidden;
            border-right: 1px solid #e2e8f0;
        }
        
        .sidebar-header {
            background: #1483be;
            color: white;
            padding: 25px 20px;
            text-align: center;
        }
        
        .sidebar-header h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .profile-section {
            padding: 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .profile-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #1483be;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            margin: 0 auto 12px;
            font-weight: bold;
        }
        
        .profile-info {
            text-align: center;
        }
        
        .profile-info h3 {
            color: #1e293b;
            font-size: 16px;
            margin-bottom: 4px;
        }
        
        .profile-info p {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 2px;
        }
        
        .students-section {
            padding: 20px;
            flex: 1;
            overflow-y: auto;
        }
        
        .nav-menu {
            padding: 15px 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .nav-item {
            display: block;
            padding: 10px 15px;
            margin-bottom: 6px;
            background: #f8fafc;
            border-radius: 8px;
            color: #1e293b;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .nav-item:hover {
            background: #e0f2fe;
            border-left-color: #1483be;
            transform: translateX(5px);
        }
        
        .nav-item.active {
            background: #1483be;
            color: white;
            border-left-color: #0f6a9a;
        }
        
        .nav-item span {
            margin-right: 8px;
        }
        
        .students-section {
            padding: 20px;
            flex: 1;
            overflow-y: auto;
            background: #f8fafc;
        }
        
        .students-section h3 {
            color: #1e293b;
            font-size: 19px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            background: white;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .student-item {
            padding: 16px;
            background: white;
            border-radius: 10px;
            margin-bottom: 14px;
            border-left: 4px solid #1483be;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }
        
        .student-item:hover {
            background: #e0f2fe;
            transform: translateX(8px);
            box-shadow: 0 4px 10px rgba(20, 131, 190, 0.2);
            border-left-color: #0f6a9a;
        }
        
        .student-item h4 {
            color: #1e293b;
            font-size: 16px;
            margin-bottom: 8px;
            font-weight: 700;
        }
        
        .student-item p {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 4px;
            line-height: 1.5;
        }
        
        .student-item .student-id {
            color: #1483be;
            font-weight: 600;
            font-size: 12px;
        }
        
        .signout-section {
            padding: 20px;
            border-top: 2px solid #e2e8f0;
        }
        
        .btn-signout {
            width: 100%;
            padding: 12px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-signout:hover {
            background: #b91c1c;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 38, 38, 0.3);
        }
        
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: #1483be;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #1483be;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .stat-card p {
            color: #1483be;
            font-size: 28px;
            font-weight: bold;
        }
        
        .submissions {
            padding: 30px;
        }
        
        .submissions h2 {
            color: #1e293b;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .submission-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .submission-card:hover {
            box-shadow: 0 4px 12px rgba(20, 131, 190, 0.15);
            border-color: #1483be;
        }
        
        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .student-info {
            flex: 1;
        }
        
        .student-info h3 {
            color: #1e293b;
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .student-info p {
            color: #64748b;
            font-size: 14px;
        }
        
        .plagiarism-badge {
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 18px;
        }
        
        .plagiarism-low {
            background: #dcfce7;
            color: #166534;
        }
        
        .plagiarism-medium {
            background: #fef3c7;
            color: #92400e;
        }
        
        .plagiarism-high {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .submission-content {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 3px solid #1483be;
        }
        
        .submission-content h4 {
            color: #475569;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .submission-content p {
            color: #1e293b;
            line-height: 1.6;
        }
        
        .feedback-section {
            margin-bottom: 15px;
        }
        
        .feedback-textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            min-height: 80px;
            transition: border-color 0.3s ease;
        }
        
        .feedback-textarea:focus {
            outline: none;
            border-color: #1483be;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-accept {
            background: #1483be;
            color: white;
        }
        
        .btn-accept:hover {
            background: #0f6a9a;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(20, 131, 190, 0.3);
        }
        
        .btn-reject {
            background: #dc2626;
            color: white;
        }
        
        .btn-reject:hover {
            background: #b91c1c;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 38, 38, 0.3);
        }
        
        .btn-delete {
            background: #64748b;
            color: white;
        }
        
        .btn-delete:hover {
            background: #475569;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(100, 116, 139, 0.3);
        }
        
        .btn-feedback {
            background: #0891b2;
            color: white;
        }
        
        .btn-feedback:hover {
            background: #0e7490;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(8, 145, 178, 0.3);
        }
        
        .btn-restore {
            background: #16a34a;
            color: white;
        }
        
        .btn-restore:hover {
            background: #15803d;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(22, 163, 74, 0.3);
        }
        
        .trash-badge {
            background: #dc2626;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 8px;
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .status-accepted {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-pending {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .no-submissions {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>📚 Similyze</h2>
            <p>Instructor Portal</p>
        </div>
        
        <div class="profile-section">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($instructor['name'], 0, 2)); ?>
            </div>
            <div class="profile-info">
                <h3><?php echo htmlspecialchars($instructor['name']); ?></h3>
                <p>📧 <?php echo htmlspecialchars($instructor['email']); ?></p>
                <p>🏫 <?php echo htmlspecialchars($instructor['department']); ?></p>
                <p>👥 <?php echo $instructor['students_count']; ?> Students Enrolled</p>
            </div>
        </div>
        
        <div class="nav-menu">
            <a href="?view=submissions" class="nav-item <?php echo $current_view === 'submissions' ? 'active' : ''; ?>">
                <span>📋</span> Submissions
            </a>
            <a href="?view=trash" class="nav-item <?php echo $current_view === 'trash' ? 'active' : ''; ?>">
                <span>🗑️</span> Trash
                <?php if (count($trash) > 0): ?>
                    <span class="trash-badge"><?php echo count($trash); ?></span>
                <?php endif; ?>
            </a>
        </div>
        
        <div class="students-section">
            <h3>👥 Enrolled Students</h3>
            <?php foreach ($enrolled_students as $student): ?>
                <div class="student-item">
                    <h4><?php echo htmlspecialchars($student['name']); ?></h4>
                    <p><?php echo htmlspecialchars($student['student_id']); ?></p>
                    <p><?php echo htmlspecialchars($student['email']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="signout-section">
            <a href="?signout=true" style="text-decoration: none;">
                <button class="btn-signout">
                    🚪 Sign Out
                </button>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="header">
                <h1>📚 Instructor Dashboard</h1>
                <p>Review and manage student submissions</p>
            </div>
            
            <div class="stats">
                <div class="stat-card">
                    <h3>Total Submissions</h3>
                    <p><?php echo count($submissions); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Pending Review</h3>
                    <p><?php echo count(array_filter($submissions, function($s) { return $s['status'] === 'pending'; })); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Accepted</h3>
                    <p><?php echo count(array_filter($submissions, function($s) { return $s['status'] === 'accepted'; })); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Rejected</h3>
                    <p><?php echo count(array_filter($submissions, function($s) { return $s['status'] === 'rejected'; })); ?></p>
                </div>
            </div>
            
            <div class="submissions">
                <?php if ($current_view === 'trash'): ?>
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
                                        <p>📧 <?php echo htmlspecialchars($submission['student_email']); ?> | 📅 <?php echo date('F j, Y g:i A', strtotime($submission['submission_date'])); ?></p>
                                    </div>
                                    
                                    <?php 
                                    $plagiarism = $submission['plagiarism_percentage'];
                                    $badge_class = 'plagiarism-low';
                                    if ($plagiarism > 50) {
                                        $badge_class = 'plagiarism-high';
                                    } elseif ($plagiarism > 25) {
                                        $badge_class = 'plagiarism-medium';
                                    }
                                    ?>
                                    <div class="plagiarism-badge <?php echo $badge_class; ?>">
                                        <?php echo $plagiarism; ?>% Plagiarism
                                    </div>
                                </div>
                                
                                <div class="submission-content">
                                    <h4>📄 Document Title: <?php echo htmlspecialchars($submission['document_title']); ?></h4>
                                    <p><?php echo nl2br(htmlspecialchars(substr($submission['content'], 0, 300))); ?><?php echo strlen($submission['content']) > 300 ? '...' : ''; ?></p>
                                </div>
                                
                                <?php if ($submission['feedback']): ?>
                                    <div class="submission-content">
                                        <h4>💬 Feedback:</h4>
                                        <p><?php echo htmlspecialchars($submission['feedback']); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                        <input type="hidden" name="action" value="restore">
                                        <button type="submit" class="btn btn-restore">↩️ Restore</button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                        <input type="hidden" name="action" value="delete_permanent">
                                        <button type="submit" class="btn btn-delete" onclick="return confirm('Are you sure you want to permanently delete this submission? This action cannot be undone.')">🗑️ Delete Permanently</button>
                                    </form>
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
                                        <?php if ($submission['status'] === 'accepted'): ?>
                                            <span class="status-badge status-accepted">✓ Accepted</span>
                                        <?php elseif ($submission['status'] === 'rejected'): ?>
                                            <span class="status-badge status-rejected">✗ Rejected</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">⏳ Pending</span>
                                        <?php endif; ?>
                                    </h3>
                                    <p>📧 <?php echo htmlspecialchars($submission['student_email']); ?> | 📅 <?php echo date('F j, Y g:i A', strtotime($submission['submission_date'])); ?></p>
                                </div>
                                
                                <?php 
                                $plagiarism = $submission['plagiarism_percentage'];
                                $badge_class = 'plagiarism-low';
                                if ($plagiarism > 50) {
                                    $badge_class = 'plagiarism-high';
                                } elseif ($plagiarism > 25) {
                                    $badge_class = 'plagiarism-medium';
                                }
                                ?>
                                <div class="plagiarism-badge <?php echo $badge_class; ?>">
                                    <?php echo $plagiarism; ?>% Plagiarism
                                </div>
                            </div>
                            
                            <div class="submission-content">
                                <h4>📄 Document Title: <?php echo htmlspecialchars($submission['document_title']); ?></h4>
                                <p><?php echo nl2br(htmlspecialchars(substr($submission['content'], 0, 300))); ?><?php echo strlen($submission['content']) > 300 ? '...' : ''; ?></p>
                            </div>
                            
                            <?php if ($submission['feedback']): ?>
                                <div class="submission-content">
                                    <h4>💬 Your Feedback:</h4>
                                    <p><?php echo htmlspecialchars($submission['feedback']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="feedback-section">
                                <form method="POST">
                                    <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                    <input type="hidden" name="action" value="feedback">
                                    <textarea 
                                        name="feedback_text" 
                                        class="feedback-textarea" 
                                        placeholder="Write your feedback to the student..."
                                    ><?php echo htmlspecialchars($submission['feedback']); ?></textarea>
                                    <div style="margin-top: 10px;">
                                        <button type="submit" class="btn btn-feedback">💬 Save Feedback</button>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="btn btn-accept">✓ Accept</button>
                                </form>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-reject">✗ Reject</button>
                                </form>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this submission?')">🗑️ Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>