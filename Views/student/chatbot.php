<?php
/**
 * Chatbot API Endpoint
 * Handles chatbot interactions with ML-based responses
 */

require_once __DIR__ . '/../../Helpers/SessionManager.php';
require_once __DIR__ . '/../../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../includes/db.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;

header('Content-Type: application/json');

$session = SessionManager::getInstance();
$auth = new AuthMiddleware();

// Check if user is logged in
if (!$session->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$currentUser = $auth->getCurrentUser();
$userId = $currentUser['id'];
$userRole = $currentUser['role'];

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message is required']);
    exit;
}

// ML-based response system
function getChatbotResponse($message, $userId, $userRole, $conn) {
    $messageLower = strtolower($message);
    
    // Get user's submission stats for context
    $submissionCount = 0;
    $avgSimilarity = 0;
    $pendingCount = 0;
    
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count, AVG(similarity) as avg_sim 
            FROM submissions 
            WHERE user_id = ? AND status != 'deleted'
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $submissionCount = intval($row['count']);
            $avgSimilarity = round(floatval($row['avg_sim'] ?? 0), 1);
        }
        $stmt->close();
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM submissions 
            WHERE user_id = ? AND status IN ('active', 'pending', 'reviewed')
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $pendingCount = intval($row['count']);
        }
        $stmt->close();
    } catch (Exception $e) {
        // Continue with default values
    }
    
    // Enhanced pattern matching with ML-like intent detection
    $intents = [
        'greeting' => [
            'keywords' => ['hello', 'hi', 'hey', 'greetings', 'good morning', 'good afternoon', 'good evening', 'sup', 'yo'],
            'weight' => 2
        ],
        'help' => [
            'keywords' => ['help', 'assist', 'support', 'guide', 'how to', 'what is', 'explain', 'how do i', 'can you', 'tell me'],
            'weight' => 2
        ],
        'submission' => [
            'keywords' => ['submit', 'submission', 'upload', 'file', 'text', 'assignment', 'work', 'document', 'paper'],
            'weight' => 3
        ],
        'plagiarism' => [
            'keywords' => ['plagiarism', 'similarity', 'score', 'percentage', 'match', 'original', 'copy', 'duplicate', 'similar'],
            'weight' => 3
        ],
        'status' => [
            'keywords' => ['status', 'pending', 'accepted', 'rejected', 'reviewed', 'approved', 'where is', 'what happened'],
            'weight' => 2
        ],
        'feedback' => [
            'keywords' => ['feedback', 'comment', 'review', 'instructor', 'teacher', 'what did', 'said', 'response'],
            'weight' => 3
        ],
        'report' => [
            'keywords' => ['report', 'download', 'view', 'detailed', 'get report', 'see report'],
            'weight' => 2
        ],
        'notification' => [
            'keywords' => ['notification', 'alert', 'update', 'new', 'message', 'notify'],
            'weight' => 2
        ],
        'thanks' => [
            'keywords' => ['thank', 'thanks', 'appreciate', 'grateful', 'ty'],
            'weight' => 1
        ],
        'goodbye' => [
            'keywords' => ['bye', 'goodbye', 'see you', 'later', 'farewell', 'cya'],
            'weight' => 1
        ]
    ];
    
    // Enhanced intent detection with weighted scoring
    $detectedIntent = null;
    $maxScore = 0;
    
    foreach ($intents as $intent => $config) {
        $score = 0;
        $keywords = $config['keywords'];
        $weight = $config['weight'];
        
        foreach ($keywords as $keyword) {
            // Exact word match (higher score)
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $messageLower)) {
                $score += $weight * 2;
            }
            // Partial match (lower score)
            elseif (strpos($messageLower, $keyword) !== false) {
                $score += $weight;
            }
        }
        
        if ($score > $maxScore) {
            $maxScore = $score;
            $detectedIntent = $intent;
        }
    }
    
    // If confidence is too low, try to infer from context
    if ($maxScore < 2) {
        // Check for question words
        if (preg_match('/\b(how|what|when|where|why|can|will|should|do|does|is|are)\b/i', $messageLower)) {
            $detectedIntent = 'help';
        }
    }
    
    // Generate contextual responses
    $responses = [];
    
    switch ($detectedIntent) {
        case 'greeting':
            $responses = [
                "Hello! 👋 I'm your AI assistant. How can I help you today?",
                "Hi there! I'm here to assist you with your plagiarism detection system. What would you like to know?",
                "Greetings! I can help you with submissions, plagiarism checks, and more. What do you need?"
            ];
            break;
            
        case 'help':
            $responses = [
                "I can help you with:\n• Submitting your work\n• Checking plagiarism scores\n• Understanding your submission status\n• Viewing feedback from instructors\n• Downloading reports\n\nWhat specific help do you need?",
                "Here's what I can assist with:\n📝 Submissions\n📊 Plagiarism reports\n✅ Status updates\n💬 Instructor feedback\n📥 Report downloads\n\nWhat would you like to know more about?"
            ];
            break;
            
        case 'submission':
            $responses = [
                "To submit your work:\n1. Go to the Submission Form\n2. Enter your text or upload a file (.txt or .docx)\n3. Select an instructor (optional)\n4. Click Submit\n\nThe system will automatically check for plagiarism!",
                "You can submit your work by:\n• Typing text directly in the form\n• Uploading a .txt or .docx file\n• Optionally selecting an instructor\n\nAfter submission, you'll get an instant plagiarism score!"
            ];
            break;
            
        case 'plagiarism':
            if ($submissionCount > 0) {
                $responses = [
                    "Your plagiarism detection works by comparing your text against all previous submissions using 5-word chunk matching. You currently have {$submissionCount} submission(s) with an average similarity of {$avgSimilarity}%.",
                    "The system analyzes your text in 5-word chunks and compares them to existing submissions. Your current average similarity score is {$avgSimilarity}% across {$submissionCount} submission(s)."
                ];
            } else {
                $responses = [
                    "Plagiarism detection compares your submission against all previous submissions using advanced pattern matching. Submit your first work to see your similarity score!",
                    "The system uses 5-word chunk analysis to detect similarities. Once you submit your work, you'll get a detailed plagiarism report!"
                ];
            }
            break;
            
        case 'status':
            if ($pendingCount > 0) {
                $responses = [
                    "You have {$pendingCount} submission(s) awaiting review. Statuses can be:\n⏳ Pending - Awaiting instructor review\n📝 Reviewed - Instructor has provided feedback\n✅ Accepted - Submission approved\n❌ Rejected - Needs revision",
                    "Your submissions status:\n• {$pendingCount} pending/reviewed\n\nCheck the History or Notifications page for detailed status updates!"
                ];
            } else {
                $responses = [
                    "Status meanings:\n⏳ Pending - Awaiting review\n📝 Reviewed - Feedback provided\n✅ Accepted - Approved\n❌ Rejected - Needs work\n\nSubmit your work to see status updates!"
                ];
            }
            break;
            
        case 'feedback':
            $responses = [
                "Instructor feedback appears in:\n1. Notifications page (for new feedback)\n2. History page (all submissions)\n\nYou'll get a notification badge when new feedback arrives!",
                "Check the 🔔 Notifications tab for new feedback, or the 📜 History tab to see all your submissions and their feedback."
            ];
            break;
            
        case 'report':
            $responses = [
                "To view/download reports:\n1. Go to History or Notifications\n2. Find your submission\n3. Click 'View Report' or 'Download Report'\n\nReports show highlighted matching text and detailed statistics!",
                "Reports are available for each submission. They include:\n• Highlighted matching text\n• Exact and partial match percentages\n• Detailed similarity breakdown\n\nAccess them from the History page!"
            ];
            break;
            
        case 'notification':
            $responses = [
                "Notifications appear when:\n• Instructor provides feedback\n• Submission is accepted/rejected\n• Status changes\n\nCheck the 🔔 icon in the sidebar for new notifications!",
                "You'll see notifications for:\n💬 New feedback\n✅ Acceptance\n❌ Rejection\n\nThe notification badge shows the count of unseen updates!"
            ];
            break;
            
        case 'thanks':
            $responses = [
                "You're welcome! 😊 Feel free to ask if you need anything else!",
                "Happy to help! If you have more questions, just ask!",
                "Anytime! I'm here whenever you need assistance! 🚀"
            ];
            break;
            
        case 'goodbye':
            $responses = [
                "Goodbye! 👋 Have a great day!",
                "See you later! Feel free to come back anytime!",
                "Farewell! Good luck with your submissions! 🎓"
            ];
            break;
            
        default:
            // Enhanced fallback with better context detection
            $hasQuestion = preg_match('/\b(how|what|when|where|why|can|will|should|do|does|is|are)\b/i', $messageLower);
            $hasSubmission = preg_match('/\b(submit|upload|file|assignment|work)\b/i', $messageLower);
            $hasPlagiarism = preg_match('/\b(plagiarism|similarity|score|match)\b/i', $messageLower);
            $hasStatus = preg_match('/\b(status|pending|accepted|rejected)\b/i', $messageLower);
            
            if ($hasQuestion && $hasSubmission) {
                $responses = [
                    "To submit your work:\n1. Go to the Submission Form on the main page\n2. Enter your text or upload a .txt/.docx file\n3. Optionally select an instructor\n4. Click Submit\n\nThe system will automatically check for plagiarism!",
                    "Submitting is easy! Use the form on the home page - you can type text or upload a file. The plagiarism check happens automatically."
                ];
            } elseif ($hasQuestion && $hasPlagiarism) {
                $responses = [
                    "Plagiarism detection compares your text against all previous submissions using 5-word chunk matching. You'll get a similarity percentage and detailed report.",
                    "The system analyzes your submission in 5-word chunks and finds matches in existing submissions. Lower percentages mean more original content!"
                ];
            } elseif ($hasQuestion && $hasStatus) {
                $responses = [
                    "Check your submission status in the History or Notifications tab. Statuses include:\n⏳ Pending - Awaiting review\n📝 Reviewed - Feedback provided\n✅ Accepted - Approved\n❌ Rejected - Needs revision",
                    "Your submission status shows in the History page. You'll also get notifications when your instructor reviews your work!"
                ];
            } elseif ($hasQuestion) {
                $responses = [
                    "I can help you with:\n• Submitting your work\n• Understanding plagiarism scores\n• Checking submission status\n• Viewing instructor feedback\n• Downloading reports\n\nWhat specifically would you like to know?",
                    "I'm here to help! You can ask me about:\n📝 Submissions\n📊 Plagiarism reports\n✅ Status updates\n💬 Instructor feedback\n📥 Report downloads\n\nWhat do you need?"
                ];
            } else {
                $responses = [
                    "I understand you're asking about: '{$message}'. Could you rephrase or be more specific? I can help with:\n• How to submit work\n• Understanding plagiarism scores\n• Checking submission status\n• Viewing instructor feedback\n• Downloading reports",
                    "I'm here to help! Try asking:\n• \"How do I submit work?\"\n• \"What is my plagiarism score?\"\n• \"Check my submission status\"\n• \"Where is my feedback?\"\n\nWhat would you like to know?"
                ];
            }
    }
    
    // Select random response from available options
    $response = $responses[array_rand($responses)];
    
    // Add contextual suggestions
    $suggestions = [];
    if ($detectedIntent === 'greeting' || $detectedIntent === null) {
        $suggestions = ["How do I submit work?", "What is plagiarism?", "Check my status"];
    } elseif ($detectedIntent === 'help') {
        $suggestions = ["How to submit?", "View reports", "Check notifications"];
    }
    
    return [
        'response' => $response,
        'intent' => $detectedIntent,
        'confidence' => $maxScore,
        'suggestions' => $suggestions
    ];
}

try {
    $result = getChatbotResponse($message, $userId, $userRole, $conn);
    
    echo json_encode([
        'success' => true,
        'message' => $result['response'],
        'intent' => $result['intent'],
        'confidence' => $result['confidence'],
        'suggestions' => $result['suggestions']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'I encountered an error. Please try again later.'
    ]);
}
?>

