<?php
/**
 * doodle - Certificates controller
 *
 * Teachers configure templates; students auto-receive certificates
 * on course completion; certificates are verifiable via unique codes.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class CertificateController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        Auth::requireLogin();
    }

    public function index(): void
    {
        $uid = Auth::id();
        $certs = Database::fetchAll(
            "SELECT cert.*, c.title AS course_title, c.id AS course_id, u.full_name AS user_name,
                    ct.name AS template_name
             FROM certificates cert
             JOIN courses c ON cert.course_id = c.id
             JOIN users u ON cert.user_id = u.id
             JOIN certificate_templates ct ON cert.template_id = ct.id
             WHERE cert.user_id = :uid
             ORDER BY cert.issued_at DESC",
            ['uid' => $uid]
        );
        $this->view('certificates/index', [
            'pageTitle'    => 'My Certificates',
            'certificates' => $certs,
        ]);
    }

    public function verify(): void
    {
        $code = trim($this->input('code', ''));
        $cert = null;
        if ($code !== '') {
            $cert = Database::fetch(
                "SELECT cert.*, c.title AS course_title, u.full_name AS user_name, u.email AS user_email
                 FROM certificates cert
                 JOIN courses c ON cert.course_id = c.id
                 JOIN users u ON cert.user_id = u.id
                 WHERE cert.verify_code = :code",
                ['code' => $code]
            );
            if (!$cert) $this->flash('error', 'No certificate found with that code.');
        }
        $this->viewRaw('certificates/verify', [
            'pageTitle' => 'Verify Certificate',
            'code'      => $code,
            'cert'      => $cert,
        ]);
    }

    public function view(int $id): void
    {
        $uid = Auth::id();
        $cert = Database::fetch(
            "SELECT cert.*, c.title AS course_title, c.teacher_id, u.full_name AS user_name,
                    ct.template_html, ct.name AS template_name
             FROM certificates cert
             JOIN courses c ON cert.course_id = c.id
             JOIN users u ON cert.user_id = u.id
             JOIN certificate_templates ct ON cert.template_id = ct.id
             WHERE cert.id = :id",
            ['id' => $id]
        );
        if (!$cert) { $this->redirect('certificates'); }
        // Permission: owner, admin, or teacher of the course
        $canView = Auth::isAdmin() || $cert['user_id'] == $uid || (Auth::isTeacher() && $cert['teacher_id'] == $uid);
        if (!$canView) { $this->flash('error', 'Access denied.'); $this->redirect(''); }
        $this->view('certificates/view', [
            'pageTitle' => 'Certificate · ' . $cert['course_title'],
            'cert'      => $cert,
        ]);
    }

    /** Teacher: list certificate templates for a course. */
    public function templates(int $courseId): void
    {
        if (!Auth::ownsCourse($courseId)) { Auth::requireRole('admin'); }
        $course = Database::fetch('SELECT * FROM courses WHERE id = :id', ['id' => $courseId]);
        $templates = Database::fetchAll('SELECT * FROM certificate_templates WHERE course_id = :cid ORDER BY id', ['cid' => $courseId]);
        $this->view('certificates/templates', [
            'pageTitle' => 'Certificate Templates · ' . $course['title'],
            'course'    => $course,
            'templates' => $templates,
        ]);
    }

    public function createTemplate(int $courseId): void
    {
        if (!Auth::ownsCourse($courseId)) { Auth::requireRole('admin'); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($this->input('name', ''));
            $minScore = (float) $this->input('min_score', 60);
            $templateHtml = $this->input('template_html', '');
            if (!empty($name)) {
                Database::insert('certificate_templates', [
                    'course_id'     => $courseId,
                    'name'          => $name,
                    'template_html' => $templateHtml,
                    'min_score'     => $minScore,
                ]);
                $this->flash('success', 'Template created.');
            }
            $this->redirect('certificates/templates/' . $courseId);
        }
    }

    public function deleteTemplate(int $id): void
    {
        $t = Database::fetch('SELECT * FROM certificate_templates WHERE id = :id', ['id' => $id]);
        if (!$t) { $this->redirect(''); }
        if (!Auth::ownsCourse($t['course_id'])) { Auth::requireRole('admin'); }
        Database::delete('certificate_templates', 'id = :id', ['id' => $id]);
        $this->flash('success', 'Template deleted.');
        $this->redirect('certificates/templates/' . $t['course_id']);
    }

    /** Issue a certificate to a student (auto-called on completion, or manually by teacher). */
    public static function issueIfNeeded(int $courseId, int $userId): void
    {
        $enroll = Database::fetch('SELECT * FROM enrollments WHERE course_id = :cid AND student_id = :sid', ['cid' => $courseId, 'sid' => $userId]);
        if (!$enroll || (float) $enroll['progress'] < 100) return;

        // Find template for this course
        $template = Database::fetch('SELECT * FROM certificate_templates WHERE course_id = :cid ORDER BY min_score DESC LIMIT 1', ['cid' => $courseId]);
        if (!$template) return;

        // Already issued?
        $existing = Database::fetch('SELECT id FROM certificates WHERE template_id = :tid AND user_id = :uid', ['tid' => $template['id'], 'uid' => $userId]);
        if ($existing) return;

        // Compute final score
        $avgRow = Database::fetch('SELECT AVG(score * 1.0 / NULLIF(max_score, 1) * 100) AS a FROM grades WHERE course_id = :cid AND student_id = :uid', ['cid' => $courseId, 'uid' => $userId]);
        $finalScore = round((float) ($avgRow['a'] ?? 0), 2);
        if ($finalScore < (float) $template['min_score']) return;

        $code = strtoupper(substr(bin2hex(random_bytes(8)), 0, 16));
        Database::insert('certificates', [
            'template_id' => $template['id'],
            'user_id'     => $userId,
            'course_id'   => $courseId,
            'verify_code' => $code,
            'final_score' => $finalScore,
        ]);
        // Notify student
        Database::insert('notifications', [
            'user_id' => $userId,
            'title'   => 'Certificate earned!',
            'body'    => "You completed the course and earned a certificate with score $finalScore%.",
            'link'    => 'certificates',
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
