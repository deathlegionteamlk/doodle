<?php
/**
 * doodle - OAuth controller (Google + GitHub)
 *
 * Lets users sign in / register via Google or GitHub OAuth.
 * Requires admin to configure client_id + secret in settings.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class OAuthController extends Controller
{
    private const GOOGLE_AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const GOOGLE_USER_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';
    private const GITHUB_AUTH_URL = 'https://github.com/login/oauth/authorize';
    private const GITHUB_TOKEN_URL = 'https://github.com/login/oauth/access_token';
    private const GITHUB_USER_URL = 'https://api.github.com/user';

    public function google(): void { $this->start('google'); }
    public function github(): void { $this->start('github'); }

    private function start(string $provider): void
    {
        $clientId = $this->getClientId($provider);
        if (!$clientId) {
            $this->flash('error', ucfirst($provider) . ' OAuth is not configured. Ask an admin to set it up.');
            $this->redirect('auth/login');
        }
        $state = bin2hex(random_bytes(16));
        Session::set('oauth_state', $state);
        Session::set('oauth_provider', $provider);
        $redirectUri = BASE_URL . '/index.php?r=oauth/callback';
        $params = [
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'state'         => $state,
            'scope'         => $provider === 'google' ? 'openid email profile' : 'read:user user:email',
        ];
        $authUrl = ($provider === 'google' ? self::GOOGLE_AUTH_URL : self::GITHUB_AUTH_URL) . '?' . http_build_query($params);
        header('Location: ' . $authUrl);
        exit;
    }

    public function callback(): void
    {
        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';
        $savedState = Session::get('oauth_state');
        $provider = Session::get('oauth_provider', '');
        Session::remove('oauth_state');
        Session::remove('oauth_provider');

        if (!$code || !$state || $state !== $savedState || !in_array($provider, ['google', 'github'], true)) {
            $this->flash('error', 'OAuth state mismatch. Please try again.');
            $this->redirect('auth/login');
        }

        // Exchange code for token
        $tokenResp = $this->exchangeCode($provider, $code);
        if (empty($tokenResp['access_token'])) {
            $this->flash('error', 'Failed to get access token from ' . $provider);
            $this->redirect('auth/login');
        }
        $accessToken = $tokenResp['access_token'];

        // Fetch user profile
        $profile = $this->fetchProfile($provider, $accessToken);
        if (empty($profile['uid']) || empty($profile['email'])) {
            $this->flash('error', 'Failed to fetch profile from ' . $provider);
            $this->redirect('auth/login');
        }

        // Check if OAuth account already linked
        $link = Database::fetch('SELECT * FROM oauth_accounts WHERE provider = :p AND provider_uid = :u', ['p' => $provider, 'u' => $profile['uid']]);
        if ($link) {
            // Log in as that user
            $user = Database::fetch('SELECT * FROM users WHERE id = :id AND status = "active"', ['id' => $link['user_id']]);
            if ($user) {
                Security::rotateSession();
                Database::update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
                Session::set('user_id', $user['id']);
                $this->flash('success', 'Signed in via ' . $provider);
                $home = $user['role'] === 'admin' ? 'admin' : ($user['role'] === 'teacher' ? 'teacher' : 'student');
                $this->redirect($home);
            }
        }

        // Try to match by email
        $existingByEmail = Database::fetch('SELECT * FROM users WHERE email = :e AND status = "active"', ['e' => $profile['email']]);
        if ($existingByEmail) {
            // Link the OAuth account to the existing user
            Database::insert('oauth_accounts', [
                'user_id'       => $existingByEmail['id'],
                'provider'      => $provider,
                'provider_uid'  => $profile['uid'],
                'email'         => $profile['email'],
                'access_token'  => $accessToken,
            ]);
            Security::rotateSession();
            Session::set('user_id', $existingByEmail['id']);
            $this->flash('success', 'Linked your ' . $provider . ' account. Welcome back!');
            $home = $existingByEmail['role'] === 'admin' ? 'admin' : ($existingByEmail['role'] === 'teacher' ? 'teacher' : 'student');
            $this->redirect($home);
        }

        // Register a brand new account
        $username = $this->makeUniqueUsername($profile['name'] ?? $profile['email']);
        $randomPassword = bin2hex(random_bytes(16)); // user can reset later
        $userId = Auth::create([
            'username'  => $username,
            'email'     => $profile['email'],
            'password'  => $randomPassword,
            'full_name' => $profile['name'] ?? $username,
            'role'      => 'student',
        ]);
        Database::insert('oauth_accounts', [
            'user_id'       => $userId,
            'provider'      => $provider,
            'provider_uid'  => $profile['uid'],
            'email'         => $profile['email'],
            'access_token'  => $accessToken,
        ]);
        Security::rotateSession();
        Session::set('user_id', $userId);
        $this->flash('success', 'Welcome to ' . APP_NAME . '! Your ' . $provider . ' account is linked.');
        $this->redirect('student');
    }

    private function exchangeCode(string $provider, string $code): array
    {
        $clientId = $this->getClientId($provider);
        $secret = $this->getSecret($provider);
        $redirectUri = BASE_URL . '/index.php?r=oauth/callback';
        $tokenUrl = $provider === 'google' ? self::GOOGLE_TOKEN_URL : self::GITHUB_TOKEN_URL;
        $post = http_build_query([
            'client_id'     => $clientId,
            'client_secret' => $secret,
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ]);
        $ch = curl_init($tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $post,
            CURLOPT_HTTPHEADER     => $provider === 'github'
                ? ['Accept: application/json']
                : ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return json_decode($resp ?: '{}', true) ?: [];
    }

    private function fetchProfile(string $provider, string $accessToken): array
    {
        $url = $provider === 'google' ? self::GOOGLE_USER_URL : self::GITHUB_USER_URL;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $provider === 'github'
                ? ['Authorization: Bearer ' . $accessToken, 'User-Agent: doodle', 'Accept: application/vnd.github+json']
                : ['Authorization: Bearer ' . $accessToken],
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($resp ?: '{}', true) ?: [];
        if ($provider === 'google') {
            return [
                'uid'   => $data['sub'] ?? '',
                'email' => $data['email'] ?? '',
                'name'  => $data['name'] ?? '',
            ];
        } else {
            // GitHub may not return email in profile — fetch separately
            if (empty($data['email'])) {
                $ch = curl_init('https://api.github.com/user/emails');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken, 'User-Agent: doodle', 'Accept: application/vnd.github+json'],
                ]);
                $emails = json_decode(curl_exec($ch) ?: '[]', true) ?: [];
                curl_close($ch);
                foreach ($emails as $e) {
                    if (!empty($e['primary'])) { $data['email'] = $e['email']; break; }
                }
            }
            return [
                'uid'   => (string) ($data['id'] ?? ''),
                'email' => $data['email'] ?? '',
                'name'  => $data['name'] ?? $data['login'] ?? '',
            ];
        }
    }

    private function getClientId(string $provider): string
    {
        $key = $provider === 'google' ? 'oauth_google_client_id' : 'oauth_github_client_id';
        return Database::fetch('SELECT setting_value FROM settings WHERE setting_key = :k', ['k' => $key])['setting_value'] ?? '';
    }

    private function getSecret(string $provider): string
    {
        $key = $provider === 'google' ? 'oauth_google_secret' : 'oauth_github_secret';
        return Database::fetch('SELECT setting_value FROM settings WHERE setting_key = :k', ['k' => $key])['setting_value'] ?? '';
    }

    private function makeUniqueUsername(string $base): string
    {
        $base = preg_replace('/[^A-Za-z0-9_\.]/', '', strtolower($base)) ?: 'user';
        $username = $base;
        $i = 1;
        while (Database::fetch('SELECT id FROM users WHERE username = :u', ['u' => $username])) {
            $username = $base . '_' . $i++;
        }
        return $username;
    }
}
