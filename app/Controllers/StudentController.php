<?php
namespace Controllers;

use Helpers\SessionManager;
use Middleware\AuthMiddleware;
use Controllers\SubmissionController;
use Helpers\Csrf;

class StudentController {
    protected $auth;
    protected $session;
    protected $submissionCtrl;
    protected $csrfToken;

    public function __construct() {
        $this->session = SessionManager::getInstance();
        $this->auth = new AuthMiddleware();
        $this->auth->requireRole('student');

        $this->submissionCtrl = new SubmissionController();
        $this->csrfToken = Csrf::token();
    }

    public function dashboard() {
        $currentUser = $this->auth->getCurrentUser();
        $userId = $currentUser['id'];

        $submissions = $this->submissionCtrl->getUserSubmissions($userId, 'active');
        $deleted = $this->submissionCtrl->getUserSubmissions($userId, 'deleted');
        $notificationCount = $this->countUnseen($submissions);

        $view = $_GET['view'] ?? 'home';
        $validViews = ['home','history','notifications','trash','chat'];
        if(!in_array($view, $validViews)) $view='home';

        require __DIR__ . '/../Views/student/student_index.php';
    }

    protected function countUnseen($submissions) {
        $count = 0;
        foreach ($submissions as $sub) {
            $hasFeedback = !empty($sub['feedback']);
            $isAccepted  = $sub['status'] === 'accepted';
            $isRejected  = $sub['status'] === 'rejected';
            $seen = $sub['notification_seen'] ?? 0;
            if (($hasFeedback || $isAccepted || $isRejected) && !$seen) $count++;
        }
        return $count;
    }
}
