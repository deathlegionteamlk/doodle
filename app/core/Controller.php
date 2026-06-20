<?php
/**
 * doodle - Base Controller
 *
 * @package doodle
 * @author  Death Legion Team
 */

class Controller
{
    public array $data = [];

    public function __construct()
    {
        // Make Auth & helpers available in all views
        $this->data['currentUser'] = Auth::user();
        $this->data['appName']     = APP_NAME;
        $this->data['appTeam']     = APP_TEAM;
        $this->data['appVersion']  = APP_VERSION;
        $this->data['baseUrl']     = BASE_URL;
        $this->data['flash']       = Session::pullFlash();
        $this->data['currentUri']  = $_GET['r'] ?? '';
    }

    protected function view(string $view, array $data = []): void
    {
        $data = array_merge($this->data, $data);
        extract($data, EXTR_SKIP);

        $headerFile = APP_DIR . '/views/layout/header.php';
        $viewFile   = APP_DIR . '/views/' . $view . '.php';
        $footerFile = APP_DIR . '/views/layout/footer.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException('View not found: ' . $viewFile);
        }
        require $headerFile;
        require $viewFile;
        require $footerFile;
    }

    /** Render a view without the standard layout (e.g. for login page). */
    protected function viewRaw(string $view, array $data = []): void
    {
        $data = array_merge($this->data, $data);
        extract($data, EXTR_SKIP);
        $viewFile = APP_DIR . '/views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new RuntimeException('View not found: ' . $viewFile);
        }
        require $viewFile;
    }

    protected function redirect(string $path = ''): void
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function redirectBack(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL;
        header('Location: ' . $referer);
        exit;
    }

    protected function json($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }
}
