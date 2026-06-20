<?php
/**
 * doodle - Calendar controller
 *
 * Aggregates assignment deadlines, quiz windows, and live sessions
 * into a single calendar view per user.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class CalendarController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        Auth::requireLogin();
    }

    public function index(): void
    {
        $uid = Auth::id();
        $month = (int) ($this->input('m', date('n')));
        $year  = (int) ($this->input('y', date('Y')));
        if ($month < 1 || $month > 12) $month = (int) date('n');
        if ($year < 2000 || $year > 2100) $year = (int) date('Y');

        // Build calendar grid
        $firstDay = mktime(0, 0, 0, $month, 1, $year);
        $daysInMonth = (int) date('t', $firstDay);
        $startWeekday = (int) date('w', $firstDay); // 0=Sun

        // Fetch events for this month
        $startDate = date('Y-m-d 00:00:00', $firstDay);
        $endDate = date('Y-m-t 23:59:59', $firstDay);

        $events = [];

        // Assignment deadlines (students: enrolled courses; teachers: their courses)
        if (Auth::isStudent()) {
            $assignments = Database::fetchAll(
                "SELECT a.id, a.title, a.due_date, c.id AS course_id, c.title AS course_title
                 FROM assignments a
                 JOIN enrollments e ON e.course_id = a.course_id AND e.student_id = :uid AND e.status = 'active'
                 JOIN courses c ON a.course_id = c.id
                 WHERE a.due_date IS NOT NULL AND a.due_date BETWEEN :start AND :end
                 ORDER BY a.due_date",
                ['uid' => $uid, 'start' => $startDate, 'end' => $endDate]
            );
        } else {
            $assignments = Database::fetchAll(
                "SELECT a.id, a.title, a.due_date, c.id AS course_id, c.title AS course_title
                 FROM assignments a JOIN courses c ON a.course_id = c.id
                 WHERE c.teacher_id = :uid AND a.due_date IS NOT NULL AND a.due_date BETWEEN :start AND :end
                 ORDER BY a.due_date",
                ['uid' => $uid, 'start' => $startDate, 'end' => $endDate]
            );
        }
        foreach ($assignments as $a) {
            $events[] = [
                'date'   => substr($a['due_date'], 0, 10),
                'type'   => 'assignment',
                'title'  => $a['title'],
                'course' => $a['course_title'],
                'link'   => 'assignment/view/' . $a['id'],
                'icon'   => 'assignment',
                'color'  => 'var(--warning)',
            ];
        }

        // Custom calendar events
        $customEvents = Database::fetchAll(
            "SELECT * FROM calendar_events WHERE user_id = :uid AND start_at BETWEEN :start AND :end ORDER BY start_at",
            ['uid' => $uid, 'start' => $startDate, 'end' => $endDate]
        );
        foreach ($customEvents as $e) {
            $events[] = [
                'date'   => substr($e['start_at'], 0, 10),
                'type'   => $e['event_type'],
                'title'  => $e['title'],
                'course' => $e['description'] ?? '',
                'link'   => 'calendar/event/' . $e['id'],
                'icon'   => 'event',
                'color'  => 'var(--primary)',
            ];
        }

        // Group events by date
        $eventsByDay = [];
        foreach ($events as $e) {
            $day = (int) substr($e['date'], 8, 2);
            $eventsByDay[$day][] = $e;
        }

        // Build calendar grid (6 weeks × 7 days)
        $grid = [];
        $day = 1;
        $prevMonthDays = (int) date('t', strtotime('-1 month', $firstDay));
        for ($week = 0; $week < 6; $week++) {
            $row = [];
            for ($d = 0; $d < 7; $d++) {
                $cellIdx = $week * 7 + $d;
                if ($cellIdx < $startWeekday) {
                    // Previous month
                    $row[] = ['day' => $prevMonthDays - $startWeekday + $cellIdx + 1, 'in_month' => false, 'events' => []];
                } elseif ($day <= $daysInMonth) {
                    $row[] = ['day' => $day, 'in_month' => true, 'events' => $eventsByDay[$day] ?? [], 'today' => ($day == (int) date('j') && $month == (int) date('n') && $year == (int) date('Y'))];
                    $day++;
                } else {
                    // Next month
                    $row[] = ['day' => $day - $daysInMonth, 'in_month' => false, 'events' => []];
                    $day++;
                }
            }
            $grid[] = $row;
            if ($day > $daysInMonth && $week >= 4) break;
        }

        // Month navigation
        $prevMonth = $month - 1; $prevYear = $year;
        if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
        $nextMonth = $month + 1; $nextYear = $year;
        if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

        // Upcoming events list (next 7 days)
        $today = date('Y-m-d');
        $weekAhead = date('Y-m-d', strtotime('+7 days'));
        $upcoming = array_filter($events, fn($e) => $e['date'] >= $today && $e['date'] <= $weekAhead);
        usort($upcoming, fn($a, $b) => $a['date'] <=> $b['date']);

        $this->view('calendar/index', [
            'pageTitle' => 'Calendar',
            'grid'      => $grid,
            'month'     => $month,
            'year'      => $year,
            'monthName' => date('F Y', $firstDay),
            'prevLink'  => 'calendar?m=' . $prevMonth . '&y=' . $prevYear,
            'nextLink'  => 'calendar?m=' . $nextMonth . '&y=' . $nextYear,
            'upcoming'  => array_slice($upcoming, 0, 8),
        ]);
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($this->input('title', ''));
            $start = $this->input('start_at', '');
            $end   = $this->input('end_at', '') ?: null;
            $desc  = trim($this->input('description', ''));
            $type  = $this->input('event_type', 'reminder');
            $location = trim($this->input('location', ''));
            $courseId = (int) $this->input('course_id', 0) ?: null;
            if (!empty($title) && !empty($start)) {
                Database::insert('calendar_events', [
                    'user_id'    => Auth::id(),
                    'course_id'  => $courseId,
                    'title'      => $title,
                    'description'=> $desc,
                    'event_type' => $type,
                    'start_at'   => $start,
                    'end_at'     => $end,
                    'location'   => $location,
                ]);
                $this->flash('success', 'Event added.');
            }
            $this->redirect('calendar');
        }
        $this->redirect('calendar');
    }

    public function delete(int $id): void
    {
        Database::delete('calendar_events', 'id = :id AND user_id = :uid', ['id' => $id, 'uid' => Auth::id()]);
        $this->flash('success', 'Event deleted.');
        $this->redirect('calendar');
    }

    /** iCal feed for the current user. */
    public function ical(): void
    {
        $uid = Auth::id();
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="doodle-calendar.ics"');
        echo "BEGIN:VCALENDAR\r\n";
        echo "VERSION:2.0\r\n";
        echo "PRODID:-//Death Legion Team//doodle//EN\r\n";
        echo "CALSCALE:GREGORIAN\r\n";

        // Assignment deadlines
        if (Auth::isStudent()) {
            $assignments = Database::fetchAll(
                "SELECT a.title, a.due_date, c.title AS course_title
                 FROM assignments a JOIN enrollments e ON e.course_id = a.course_id
                 JOIN courses c ON a.course_id = c.id
                 WHERE e.student_id = :uid AND e.status = 'active' AND a.due_date IS NOT NULL",
                ['uid' => $uid]
            );
        } else {
            $assignments = Database::fetchAll(
                "SELECT a.title, a.due_date, c.title AS course_title
                 FROM assignments a JOIN courses c ON a.course_id = c.id
                 WHERE c.teacher_id = :uid AND a.due_date IS NOT NULL",
                ['uid' => $uid]
            );
        }
        foreach ($assignments as $a) {
            $dt = gmdate('Ymd\THis\Z', strtotime($a['due_date']));
            echo "BEGIN:VEVENT\r\n";
            echo "UID:" . md5($a['title'] . $a['due_date']) . "@doodle\r\n";
            echo "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
            echo "DTSTART:$dt\r\n";
            echo "DTEND:$dt\r\n";
            echo "SUMMARY:" . $this->icalEscape($a['title'] . ' (' . $a['course_title'] . ')') . "\r\n";
            echo "END:VEVENT\r\n";
        }
        // Custom events
        $custom = Database::fetchAll('SELECT * FROM calendar_events WHERE user_id = :uid', ['uid' => $uid]);
        foreach ($custom as $e) {
            $dt = gmdate('Ymd\THis\Z', strtotime($e['start_at']));
            $dtEnd = $e['end_at'] ? gmdate('Ymd\THis\Z', strtotime($e['end_at'])) : $dt;
            echo "BEGIN:VEVENT\r\n";
            echo "UID:evt-" . $e['id'] . "@doodle\r\n";
            echo "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
            echo "DTSTART:$dt\r\n";
            echo "DTEND:$dtEnd\r\n";
            echo "SUMMARY:" . $this->icalEscape($e['title']) . "\r\n";
            if (!empty($e['description'])) echo "DESCRIPTION:" . $this->icalEscape($e['description']) . "\r\n";
            if (!empty($e['location'])) echo "LOCATION:" . $this->icalEscape($e['location']) . "\r\n";
            echo "END:VEVENT\r\n";
        }
        echo "END:VCALENDAR\r\n";
        exit;
    }

    private function icalEscape(string $s): string
    {
        return str_replace(["\\", "\n", "\r", ";", ","], ["\\\\", "\\n", "", "\\;", "\\,"], $s);
    }
}
