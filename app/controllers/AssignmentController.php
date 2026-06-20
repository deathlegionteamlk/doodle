<?php
/**
 * doodle - Assignment controller
 *
 * Student-facing assignment detail page.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class AssignmentController extends Controller
{
    public function view(int $id): void
    {
        Auth::requireLogin();
        $a = Database::fetch(
            "SELECT a.*, c.title AS course_title, c.id AS course_id, c.teacher_id
             FROM assignments a
             JOIN courses c ON a.course_id = c.id
             WHERE a.id = :id",
            ['id' => $id]
        );
        if (!$a) { http_response_code(404); $this->view('errors/404', ['message' => 'Assignment not found.']); return; }

        $isOwner = Auth::isAdmin() || (Auth::isTeacher() && $a['teacher_id'] == Auth::id());
        if (!$isOwner && !Auth::isEnrolled($a['course_id'])) {
            $this->flash('error', 'You must be enrolled to view this assignment.');
            $this->redirect('course/view/' . $a['course_id']);
        }

        $submission = Auth::isStudent() ? Database::fetch(
            'SELECT * FROM submissions WHERE assignment_id = :aid AND student_id = :sid',
            ['aid' => $id, 'sid' => Auth::id()]
        ) : null;

        $this->view('assignment/view', [
            'pageTitle' => $a['title'],
            'assignment' => $a,
            'submission' => $submission,
            'isOwner' => $isOwner,
        ]);
    }
}
