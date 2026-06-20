<?php
/**
 * doodle - Course catalog
 *
 * @package doodle
 * @author  Death Legion Team
 */

class CatalogController extends Controller
{
    public function index(): void
    {
        $q          = trim($_GET['q'] ?? '');
        $categoryId = (int) ($_GET['cat'] ?? 0);
        $level      = $_GET['level'] ?? '';
        $page       = max(1, (int) ($_GET['p'] ?? 1));
        $offset     = ($page - 1) * PER_PAGE;

        $where  = ["c.status = 'published'", "c.visibility = 'public'"];
        $params = [];

        if ($q !== '') {
            $where[] = "(c.title LIKE :q OR c.description LIKE :q2)";
            $params['q']  = '%' . $q . '%';
            $params['q2'] = '%' . $q . '%';
        }
        if ($categoryId > 0) {
            $where[] = "c.category_id = :cat";
            $params['cat'] = $categoryId;
        }
        if (in_array($level, ['beginner','intermediate','advanced'], true)) {
            $where[] = "c.level = :level";
            $params['level'] = $level;
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) Database::fetch("SELECT COUNT(*) AS c FROM courses c WHERE $whereSql", $params)['c'];
        $courses = Database::fetchAll(
            "SELECT c.*, u.full_name AS teacher_name, cat.name AS category_name
             FROM courses c
             JOIN users u ON c.teacher_id = u.id
             LEFT JOIN categories cat ON c.category_id = cat.id
             WHERE $whereSql
             ORDER BY c.enroll_count DESC, c.created_at DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, ['limit' => PER_PAGE, 'offset' => $offset])
        );
        $categories = Database::fetchAll("SELECT * FROM categories ORDER BY sort_order, name");

        $baseUrl = 'catalog?' . http_build_query(array_filter(['r' => 'catalog', 'q' => $q, 'cat' => $categoryId, 'level' => $level]));
        $pagination = paginate($total, PER_PAGE, $page, $baseUrl);

        $this->view('catalog/index', [
            'pageTitle'  => 'Course Catalog',
            'courses'    => $courses,
            'categories' => $categories,
            'total'      => $total,
            'q'          => $q,
            'categoryId' => $categoryId,
            'level'      => $level,
            'pagination' => $pagination,
        ]);
    }
}
