<?php

/**
 * ChatbotService
 * Encapsulates chatbot intent detection and response generation so it can be unit tested.
 */
class ChatbotService
{
    /**
     * Generate chatbot response for a given message and user context.
     *
     * @param string   $message
     * @param int      $userId
     * @param string   $userRole
     * @param \mysqli  $conn
     * @return array{response:string,intent:?string,confidence:int,suggestions:array}
     */
    public static function getResponse(string $message, int $userId, string $userRole, \mysqli $conn): array
    {
        $messageLower = strtolower($message);

        // Get user's submission stats for context
        $submissionCount = 0;
        $avgSimilarity   = 0;
        $pendingCount    = 0;

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
                $avgSimilarity   = round(floatval($row['avg_sim'] ?? 0), 1);
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
        } catch (\Exception $e) {
            // Continue with default values
        }

        // Enhanced pattern matching with ML-like intent detection
        $intents = [
            'greeting' => [
                'keywords' => ['hello', 'hi', 'hey', 'greetings', 'good morning', 'good afternoon', 'good evening', 'sup', 'yo'],
                'weight'   => 2,
            ],
            'help' => [
                'keywords' => ['help', 'assist', 'support', 'guide', 'how to', 'what is', 'explain', 'how do i', 'can you', 'tell me'],
                'weight'   => 2,
            ],
            'submission' => [
                'keywords' => ['submit', 'submission', 'upload', 'file', 'text', 'assignment', 'work', 'document', 'paper'],
                'weight'   => 3,
            ],
            'plagiarism' => [
                'keywords' => ['plagiarism', 'similarity', 'score', 'percentage', 'match', 'original', 'copy', 'duplicate', 'similar'],
                'weight'   => 3,
            ],
            'status' => [
                'keywords' => ['status', 'pending', 'accepted', 'rejected', 'reviewed', 'approved', 'where is', 'what happened'],
                'weight'   => 2,
            ],
            'feedback' => [
                'keywords' => ['feedback', 'comment', 'review', 'instructor', 'teacher', 'what did', 'said', 'response'],
                'weight'   => 3,
            ],
            'report' => [
                'keywords' => ['report', 'download', 'view', 'detailed', 'get report', 'see report'],
                'weight'   => 2,
            ],
            'notification' => [
                'keywords' => ['notification', 'alert', 'update', 'new', 'message', 'notify'],
                'weight'   => 2,
            ],
            'thanks' => [
                'keywords' => ['thank', 'thanks', 'appreciate', 'grateful', 'ty'],
                'weight'   => 1,
            ],
            'goodbye' => [
                'keywords' => ['bye', 'goodbye', 'see you', 'later', 'farewell', 'cya'],
                'weight'   => 1,
            ],
            'improvement' => [
                'keywords' => ['improve', 'better', 'enhance', 'tips', 'advice', 'suggestions', 'how to improve', 'get better', 'reduce', 'lower', 'decrease'],
                'weight'   => 3,
            ],
            'question' => [
                'keywords' => ['how', 'what', 'why', 'when', 'where', 'can i', 'should i', 'is it', 'does', 'do'],
                'weight'   => 2,
            ],
        ];

        // Enhanced intent detection with weighted scoring
        $detectedIntent = null;
        $maxScore       = 0;

        foreach ($intents as $intent => $config) {
            $score    = 0;
            $keywords = $config['keywords'];
            $weight   = $config['weight'];

            foreach ($keywords as $keyword) {
                // Exact word match (higher score)
                if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $messageLower)) {
                    $score += $weight * 2;
                } elseif (strpos($messageLower, $keyword) !== false) {
                    // Partial match (lower score)
                    $score += $weight;
                }
            }

            if ($score > $maxScore) {
                $maxScore       = $score;
                $detectedIntent = $intent;
            }
        }

        // If confidence is too low, try to infer from context
        if ($maxScore < 2) {
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
                    "Greetings! I can help you with submissions, plagiarism checks, and more. What do you need?",
                ];
                break;

            case 'submission':
                if ($submissionCount > 0) {
                    $responses = [
                        "You have submitted {$submissionCount} " . ($submissionCount === 1 ? 'assignment' : 'assignments') . " so far. " . 
                        ($pendingCount > 0 ? "You have {$pendingCount} " . ($pendingCount === 1 ? 'submission' : 'submissions') . " pending review." : "All your submissions have been reviewed."),
                        "Based on your account, you've made {$submissionCount} " . ($submissionCount === 1 ? 'submission' : 'submissions') . ". " .
                        "To submit a new assignment, go to the Home page and use the submission form to upload your file or paste your text.",
                        "You currently have {$submissionCount} " . ($submissionCount === 1 ? 'submission' : 'submissions') . " in the system. " .
                        "You can view all your submissions in the History section.",
                    ];
                } else {
                    $responses = [
                        "You haven't made any submissions yet. To submit your work, go to the Home page and either upload a file or paste your text content.",
                        "No submissions found. Start by going to the Home page and submitting your first assignment using the submission form.",
                    ];
                }
                break;

            case 'plagiarism':
                if ($submissionCount > 0) {
                    if ($avgSimilarity > 0) {
                        $responses = [
                            "Your average plagiarism similarity score is {$avgSimilarity}%. " .
                            ($avgSimilarity < 20 ? "That's excellent! Your work shows high originality." : 
                             ($avgSimilarity < 50 ? "That's good! Your work has moderate similarity, which is acceptable." : 
                              "Your work has higher similarity. Consider reviewing and citing sources properly.")),
                            "Based on your {$submissionCount} " . ($submissionCount === 1 ? 'submission' : 'submissions') . ", your average similarity is {$avgSimilarity}%. " .
                            "You can view detailed plagiarism reports for each submission in the History section.",
                        ];
                    } else {
                        $responses = [
                            "Plagiarism detection analyzes your submissions for similarity with other works. " .
                            "You have {$submissionCount} " . ($submissionCount === 1 ? 'submission' : 'submissions') . " in the system. " .
                            "Check the History section to see detailed plagiarism reports for each submission.",
                        ];
                    }
                } else {
                    $responses = [
                        "Plagiarism detection checks your work for similarity with other documents in the database. " .
                        "After you submit your work, you'll receive a similarity percentage score. " .
                        "Lower scores indicate more original work. Scores below 20% are considered excellent.",
                    ];
                }
                break;

            case 'status':
                try {
                    $stmt = $conn->prepare("
                        SELECT status, COUNT(*) as count 
                        FROM submissions 
                        WHERE user_id = ? AND status != 'deleted'
                        GROUP BY status
                    ");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $statusCounts = [];
                    while ($row = $result->fetch_assoc()) {
                        $statusCounts[$row['status']] = $row['count'];
                    }
                    $stmt->close();

                    if (!empty($statusCounts)) {
                        $statusText = [];
                        foreach ($statusCounts as $status => $count) {
                            $statusName = ucfirst($status);
                            $statusText[] = "{$count} " . ($count === 1 ? 'submission' : 'submissions') . " with status '{$statusName}'";
                        }
                        $responses = [
                            "Here's your submission status: " . implode(', ', $statusText) . ". " .
                            "You can view detailed status for each submission in the History section.",
                            "Current status breakdown: " . implode(', ', $statusText) . ".",
                        ];
                    } else {
                        $responses = [
                            "You don't have any active submissions. Submit your work to see status updates.",
                        ];
                    }
                } catch (\Exception $e) {
                    $responses = [
                        "I couldn't retrieve your submission status right now. Please check the History section for details.",
                    ];
                }
                break;

            case 'feedback':
                try {
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) as count 
                        FROM submissions 
                        WHERE user_id = ? AND feedback IS NOT NULL AND feedback != '' AND status != 'deleted'
                    ");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $feedbackCount = 0;
                    if ($row = $result->fetch_assoc()) {
                        $feedbackCount = intval($row['count']);
                    }
                    $stmt->close();

                    if ($feedbackCount > 0) {
                        $responses = [
                            "You have received feedback on {$feedbackCount} " . ($feedbackCount === 1 ? 'submission' : 'submissions') . ". " .
                            "Check the History or Notifications section to read your instructor's feedback.",
                            "Great news! You have feedback on {$feedbackCount} " . ($feedbackCount === 1 ? 'of your submissions' : 'of your submissions') . ". " .
                            "Visit the Notifications page to see the detailed feedback from your instructor.",
                        ];
                    } else {
                        $responses = [
                            "You don't have any feedback yet. Once your instructor reviews your submissions, you'll receive feedback. " .
                            "Check the Notifications section regularly for updates.",
                            "No feedback available yet. Your submissions are being reviewed. " .
                            "You'll be notified when your instructor provides feedback.",
                        ];
                    }
                } catch (\Exception $e) {
                    $responses = [
                        "I couldn't check your feedback right now. Please visit the Notifications section to see any feedback from your instructor.",
                    ];
                }
                break;

            case 'report':
                if ($submissionCount > 0) {
                    $responses = [
                        "You can view detailed plagiarism reports for your submissions in the History section. " .
                        "Each report shows similarity percentages, exact matches, and partial matches with other documents.",
                        "To view a report, go to the History section and click on any submission. " .
                        "You'll see a detailed plagiarism analysis report with similarity scores and matched content.",
                    ];
                } else {
                    $responses = [
                        "Reports are generated after you submit your work. " .
                        "Once you submit an assignment, you'll be able to view detailed plagiarism reports in the History section.",
                    ];
                }
                break;

            case 'notification':
                try {
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) as count 
                        FROM submissions 
                        WHERE user_id = ? 
                        AND ((feedback IS NOT NULL AND feedback != '') OR status IN ('accepted', 'rejected'))
                        AND notification_seen = 0
                        AND status != 'deleted'
                    ");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $unseenCount = 0;
                    if ($row = $result->fetch_assoc()) {
                        $unseenCount = intval($row['count']);
                    }
                    $stmt->close();

                    if ($unseenCount > 0) {
                        $responses = [
                            "You have {$unseenCount} new " . ($unseenCount === 1 ? 'notification' : 'notifications') . "! " .
                            "Check the Notifications section to see updates about your submissions.",
                            "There are {$unseenCount} unread " . ($unseenCount === 1 ? 'notification' : 'notifications') . " waiting for you. " .
                            "Visit the Notifications page to see what's new.",
                        ];
                    } else {
                        $responses = [
                            "You're all caught up! No new notifications at the moment. " .
                            "You'll be notified when your submissions are reviewed or when you receive feedback.",
                        ];
                    }
                } catch (\Exception $e) {
                    $responses = [
                        "Check the Notifications section to see any updates about your submissions.",
                    ];
                }
                break;

            case 'help':
                $responses = [
                    "I can help you with:\n" .
                    "• Submissions: Upload files or paste text\n" .
                    "• Plagiarism scores: Check similarity percentages\n" .
                    "• Status: See if your work is pending, accepted, or rejected\n" .
                    "• Feedback: Read instructor comments\n" .
                    "• Reports: View detailed plagiarism analysis\n\n" .
                    "Just ask me about any of these topics!",
                    "Here's what I can help with:\n" .
                    "📄 Submissions - Submit and manage your work\n" .
                    "📊 Plagiarism - Check similarity scores\n" .
                    "📋 Status - Track submission status\n" .
                    "💬 Feedback - Read instructor comments\n" .
                    "📑 Reports - View detailed analysis\n\n" .
                    "What would you like to know?",
                ];
                break;

            case 'thanks':
                $responses = [
                    "You're welcome! 😊 Feel free to ask if you need anything else.",
                    "Happy to help! If you have more questions, just ask!",
                    "Glad I could assist! Don't hesitate to reach out if you need anything.",
                ];
                break;

            case 'goodbye':
                $responses = [
                    "Goodbye! 👋 Have a great day!",
                    "See you later! Feel free to come back anytime.",
                    "Take care! Come back if you need help.",
                ];
                break;

            case 'improvement':
                // Get detailed stats for personalized improvement advice
                try {
                    $stmt = $conn->prepare("
                        SELECT 
                            AVG(similarity) as avg_sim,
                            MAX(similarity) as max_sim,
                            COUNT(*) as total,
                            SUM(CASE WHEN similarity > 50 THEN 1 ELSE 0 END) as high_sim_count,
                            SUM(CASE WHEN feedback IS NOT NULL AND feedback != '' THEN 1 ELSE 0 END) as feedback_count
                        FROM submissions 
                        WHERE user_id = ? AND status != 'deleted'
                    ");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stats = $result->fetch_assoc();
                    $stmt->close();

                    $avgSim = round(floatval($stats['avg_sim'] ?? 0), 1);
                    $maxSim = round(floatval($stats['max_sim'] ?? 0), 1);
                    $total = intval($stats['total'] ?? 0);
                    $highSimCount = intval($stats['high_sim_count'] ?? 0);
                    $feedbackCount = intval($stats['feedback_count'] ?? 0);

                    if ($total > 0) {
                        $improvementTips = [];
                        
                        // Analyze plagiarism scores
                        if ($avgSim > 50) {
                            $improvementTips[] = "📊 Your average similarity is {$avgSim}%, which is quite high. Focus on:\n" .
                                "• Paraphrasing content in your own words\n" .
                                "• Properly citing all sources\n" .
                                "• Adding your own analysis and insights\n" .
                                "• Using multiple sources instead of relying on one";
                        } elseif ($avgSim > 30) {
                            $improvementTips[] = "📊 Your average similarity is {$avgSim}%, which is moderate. To improve:\n" .
                                "• Ensure all quotes are properly cited\n" .
                                "• Add more original analysis to your work\n" .
                                "• Use a variety of sources\n" .
                                "• Review your paraphrasing techniques";
                        } elseif ($avgSim > 0) {
                            $improvementTips[] = "📊 Great job! Your average similarity is {$avgSim}%, which shows good originality. " .
                                "Keep it up by maintaining proper citations and adding your unique perspective.";
                        }

                        // Check for high similarity submissions
                        if ($highSimCount > 0) {
                            $improvementTips[] = "⚠️ You have {$highSimCount} " . ($highSimCount === 1 ? 'submission' : 'submissions') . 
                                " with similarity above 50%. Review those submissions and focus on:\n" .
                                "• Better paraphrasing\n" .
                                "• More original content\n" .
                                "• Proper citation format";
                        }

                        // Check for feedback
                        if ($feedbackCount > 0) {
                            $improvementTips[] = "💬 You've received feedback on {$feedbackCount} " . 
                                ($feedbackCount === 1 ? 'submission' : 'submissions') . 
                                ". Review your instructor's comments in the Notifications section - they contain valuable improvement suggestions!";
                        } else {
                            $improvementTips[] = "💬 Check the Notifications section regularly for instructor feedback. " .
                                "Their comments will help you understand what to improve.";
                        }

                        // General tips
                        $improvementTips[] = "💡 General improvement tips:\n" .
                            "• Start assignments early to allow time for revision\n" .
                            "• Use plagiarism checker before submitting\n" .
                            "• Understand the difference between paraphrasing and copying\n" .
                            "• Always cite sources using proper format\n" .
                            "• Add your own analysis and critical thinking\n" .
                            "• Review your work multiple times before submission";

                        $responses = [
                            implode("\n\n", $improvementTips),
                            "Based on your {$total} " . ($total === 1 ? 'submission' : 'submissions') . 
                            ", here's how you can improve:\n\n" . implode("\n\n", array_slice($improvementTips, 0, 2)),
                        ];
                    } else {
                        $responses = [
                            "Since you haven't submitted any work yet, here are tips to get started:\n\n" .
                            "📝 Before submitting:\n" .
                            "• Understand the assignment requirements clearly\n" .
                            "• Research from multiple credible sources\n" .
                            "• Take notes in your own words\n" .
                            "• Always cite your sources properly\n" .
                            "• Review your work for clarity and originality\n\n" .
                            "💡 Best practices:\n" .
                            "• Start early to allow time for revision\n" .
                            "• Use proper citation format (APA, MLA, etc.)\n" .
                            "• Add your own analysis and insights\n" .
                            "• Proofread before submitting",
                        ];
                    }
                } catch (\Exception $e) {
                    $responses = [
                        "Here are general tips to improve your academic work:\n\n" .
                        "📝 Writing Tips:\n" .
                        "• Paraphrase content in your own words\n" .
                        "• Always cite sources properly\n" .
                        "• Add your own analysis and insights\n" .
                        "• Use multiple sources, not just one\n\n" .
                        "💡 Best Practices:\n" .
                        "• Start assignments early\n" .
                        "• Review your work before submitting\n" .
                        "• Check for plagiarism before final submission\n" .
                        "• Read instructor feedback carefully",
                    ];
                }
                break;

            case 'question':
                // Handle various "how to" and "what is" questions
                if (preg_match('/\b(how|how to|how do|how can)\b/i', $messageLower)) {
                    if (preg_match('/\b(submit|upload|send|turn in)\b/i', $messageLower)) {
                        $responses = [
                            "To submit your work:\n\n" .
                            "1. Go to the Home page\n" .
                            "2. Fill in the submission form:\n" .
                            "   • Enter your teacher's name\n" .
                            "   • Add a title for your submission\n" .
                            "   • Either upload a file OR paste your text content\n" .
                            "3. Click 'Submit Assignment'\n\n" .
                            "💡 Tip: You can submit either a file (Word, PDF, etc.) or paste text directly.",
                        ];
                    } elseif (preg_match('/\b(reduce|lower|decrease|improve|better)\b.*\b(similarity|plagiarism|score|percentage)\b/i', $messageLower)) {
                        $responses = [
                            "To reduce your plagiarism similarity score:\n\n" .
                            "📝 Writing Techniques:\n" .
                            "• Paraphrase: Rewrite ideas in your own words\n" .
                            "• Cite properly: Always give credit to sources\n" .
                            "• Add analysis: Include your own insights and opinions\n" .
                            "• Use quotes: When using exact words, use quotation marks\n\n" .
                            "💡 Best Practices:\n" .
                            "• Use multiple sources, not just one\n" .
                            "• Take notes in your own words while researching\n" .
                            "• Review and revise your work multiple times\n" .
                            "• Check your work before submitting",
                        ];
                    } elseif (preg_match('/\b(what is|what\'s|explain)\b.*\b(plagiarism|similarity)\b/i', $messageLower)) {
                        $responses = [
                            "Plagiarism is using someone else's work or ideas without giving proper credit.\n\n" .
                            "📊 Similarity Score:\n" .
                            "• Shows how much your work matches other documents\n" .
                            "• Lower scores (0-20%) = Excellent originality\n" .
                            "• Moderate scores (20-40%) = Good, with proper citations\n" .
                            "• Higher scores (40%+) = Needs improvement\n\n" .
                            "✅ To avoid plagiarism:\n" .
                            "• Always cite your sources\n" .
                            "• Paraphrase in your own words\n" .
                            "• Add your own analysis\n" .
                            "• Use quotation marks for direct quotes",
                        ];
                    } else {
                        $responses = [
                            "I can help you with various topics! Try asking:\n" .
                            "• 'How do I submit work?'\n" .
                            "• 'How to reduce plagiarism score?'\n" .
                            "• 'What is plagiarism?'\n" .
                            "• 'How to check my status?'\n\n" .
                            "Or just ask me about your submissions, scores, or feedback!",
                        ];
                    }
                } else {
                    // Generic question response
                    $responses = [
                        "I'm here to help! You can ask me:\n" .
                        "• About your submissions and their status\n" .
                        "• About your plagiarism scores\n" .
                        "• How to submit work\n" .
                        "• How to improve your scores\n" .
                        "• About feedback from instructors\n\n" .
                        "What would you like to know?",
                    ];
                }
                break;
        }

        // If no specific response set above, fall back to generic help
        if (empty($responses)) {
            $responses = [
                "I'm here to help! You can ask me about submissions, plagiarism scores, status, feedback, or reports. " .
                "Try asking: 'Tell me about my submissions' or 'What's my plagiarism score?'",
            ];
        }

        $response = $responses[array_rand($responses)];

        // Generate context-aware suggestions
        $suggestions = [];
        switch ($detectedIntent) {
            case 'greeting':
            case null:
                $suggestions = ["Tell me about my submissions", "What's my plagiarism score?", "How can I improve myself"];
                break;
            case 'submission':
                $suggestions = ["How do I submit?", "View my submissions", "Check submission status"];
                break;
            case 'plagiarism':
                $suggestions = ["What's a good score?", "View my reports", "How to reduce similarity?"];
                break;
            case 'status':
                $suggestions = ["View all submissions", "Check notifications", "See feedback"];
                break;
            case 'feedback':
                $suggestions = ["View notifications", "Check submission status", "See all feedback"];
                break;
            case 'report':
                $suggestions = ["View my reports", "Download report", "Check plagiarism score"];
                break;
            case 'notification':
                $suggestions = ["View notifications", "Check status", "See feedback"];
                break;
            case 'improvement':
                $suggestions = ["How to reduce plagiarism?", "View my scores", "Check feedback"];
                break;
            case 'question':
                $suggestions = ["How do I submit?", "What is plagiarism?", "How to improve?"];
                break;
            default:
                $suggestions = ["Tell me about submissions", "What is plagiarism?", "How can I improve?"];
        }

        return [
            'response'    => $response,
            'intent'      => $detectedIntent,
            'confidence'  => $maxScore,
            'suggestions' => $suggestions,
        ];
    }
}


