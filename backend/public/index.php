<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// 1. Bulletproof Static Asset Interceptor
$rootDir = dirname(dirname(__DIR__));
$publicDir = __DIR__;

// Check inside backend/public (e.g. /assets/...)
$staticCandidate = $publicDir . $requestUri;
if (file_exists($staticCandidate) && !is_dir($staticCandidate)) {
    serveStaticFile($staticCandidate);
    exit;
}

// Check inside frontend/public
$frontendCandidate = $rootDir . $requestUri;
if (file_exists($frontendCandidate) && !is_dir($frontendCandidate)) {
    serveStaticFile($frontendCandidate);
    exit;
}

// Check if request is /assets/... mapped to frontend/public/
if (str_starts_with($requestUri, '/assets/')) {
    $subPath = substr($requestUri, strlen('/assets/'));
    $mapped = $rootDir . '/frontend/public/' . $subPath;
    if (file_exists($mapped) && !is_dir($mapped)) {
        serveStaticFile($mapped);
        exit;
    }
}

function serveStaticFile(string $filePath): void {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimes = [
        'css'   => 'text/css; charset=UTF-8',
        'js'    => 'application/javascript; charset=UTF-8',
        'json'  => 'application/json; charset=UTF-8',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'svg'   => 'image/svg+xml',
        'webp'  => 'image/webp',
        'ico'   => 'image/x-icon',
        'woff2' => 'font/woff2',
        'woff'  => 'font/woff',
        'ttf'   => 'font/ttf',
        'xml'   => 'application/xml; charset=UTF-8'
    ];
    if (isset($mimes[$ext])) {
        header("Content-Type: {$mimes[$ext]}");
        header("Cache-Control: public, max-age=86400");
        readfile($filePath);
        exit;
    }
}

// Enable CORS and Security Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Internal-Secret, X-Requested-With");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Controllers/JobController.php';
require_once __DIR__ . '/../app/Controllers/ExamController.php';
require_once __DIR__ . '/../app/Controllers/ArticleController.php';
require_once __DIR__ . '/../app/Controllers/SyncController.php';
require_once __DIR__ . '/../app/Controllers/AdminController.php';

use App\Controllers\JobController;
use App\Controllers\ExamController;
use App\Controllers\ArticleController;
use App\Controllers\SyncController;
use App\Controllers\AdminController;

// Handle API Endpoints
if (str_starts_with($requestUri, '/api/v1/')) {
    header('Content-Type: application/json');
    $apiPath = substr($requestUri, strlen('/api/v1/'));

    // Admin Auth Routes
    if ($apiPath === 'admin/login' && $method === 'POST') {
        (new AdminController())->login();
        exit;
    }
    if ($apiPath === 'admin/logout' && ($method === 'POST' || $method === 'GET')) {
        (new AdminController())->logout();
        exit;
    }

    // Public APIs
    if ($apiPath === 'jobs' && $method === 'GET') {
        (new JobController())->listJobs();
        exit;
    }
    if (str_starts_with($apiPath, 'jobs/') && $method === 'GET') {
        $slug = substr($apiPath, strlen('jobs/'));
        (new JobController())->getJobBySlug($slug);
        exit;
    }

    if ($apiPath === 'exams' && $method === 'GET') {
        (new ExamController())->listExams();
        exit;
    }
    if (str_starts_with($apiPath, 'exams/') && $method === 'GET') {
        $slug = substr($apiPath, strlen('exams/'));
        (new ExamController())->getExamBySlug($slug);
        exit;
    }

    if ($apiPath === 'articles' && $method === 'GET') {
        (new ArticleController())->listArticles();
        exit;
    }
    if (str_starts_with($apiPath, 'articles/') && $method === 'GET') {
        $slug = substr($apiPath, strlen('articles/'));
        (new ArticleController())->getArticleBySlug($slug);
        exit;
    }

    if ($apiPath === 'internal/sync-jobs' && $method === 'POST') {
        (new SyncController())->syncJobs();
        exit;
    }

    // Protected Admin APIs
    if (str_starts_with($apiPath, 'admin/')) {
        if (!AdminController::isAuthenticated()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized: Admin login session required.'
            ]);
            exit;
        }

        if ($apiPath === 'admin/metrics' && $method === 'GET') {
            (new AdminController())->getDashboardMetrics();
            exit;
        }
        if ($apiPath === 'admin/trigger' && ($method === 'POST' || $method === 'GET')) {
            (new AdminController())->triggerAutomation();
            exit;
        }
        if ($apiPath === 'admin/sources' && $method === 'GET') {
            (new AdminController())->listSources();
            exit;
        }
        if ($apiPath === 'admin/update-status' && $method === 'POST') {
            (new AdminController())->updateJobStatus();
            exit;
        }
        if ($apiPath === 'admin/recruitments/get' && $method === 'GET') {
            (new AdminController())->getJob();
            exit;
        }
        if ($apiPath === 'admin/recruitments/create' && $method === 'POST') {
            (new AdminController())->createJob();
            exit;
        }
        if ($apiPath === 'admin/recruitments/update' && $method === 'POST') {
            (new AdminController())->updateJob();
            exit;
        }
        if ($apiPath === 'admin/recruitments/delete' && $method === 'POST') {
            (new AdminController())->deleteJob();
            exit;
        }
        if ($apiPath === 'admin/exams/get' && $method === 'GET') {
            (new AdminController())->getExam();
            exit;
        }
        if ($apiPath === 'admin/exams/create' && $method === 'POST') {
            (new AdminController())->createExam();
            exit;
        }
        if ($apiPath === 'admin/exams/update' && $method === 'POST') {
            (new AdminController())->updateExam();
            exit;
        }
        if ($apiPath === 'admin/exams/delete' && $method === 'POST') {
            (new AdminController())->deleteExam();
            exit;
        }
        if ($apiPath === 'admin/articles/get' && $method === 'GET') {
            (new AdminController())->getArticle();
            exit;
        }
        if ($apiPath === 'admin/articles/create' && $method === 'POST') {
            (new AdminController())->createArticle();
            exit;
        }
        if ($apiPath === 'admin/articles/update' && $method === 'POST') {
            (new AdminController())->updateArticle();
            exit;
        }
        if ($apiPath === 'admin/articles/delete' && $method === 'POST') {
            (new AdminController())->deleteArticle();
            exit;
        }
        if ($apiPath === 'admin/commissions/get' && $method === 'GET') {
            (new AdminController())->getCommission();
            exit;
        }
        if ($apiPath === 'admin/commissions/create' && $method === 'POST') {
            (new AdminController())->createCommission();
            exit;
        }
        if ($apiPath === 'admin/commissions/update' && $method === 'POST') {
            (new AdminController())->updateCommission();
            exit;
        }
        if ($apiPath === 'admin/commissions/delete' && $method === 'POST') {
            (new AdminController())->deleteCommission();
            exit;
        }
        if ($apiPath === 'admin/events/get' && $method === 'GET') {
            (new AdminController())->getEvent();
            exit;
        }
        if ($apiPath === 'admin/events/create' && $method === 'POST') {
            (new AdminController())->createEvent();
            exit;
        }
        if ($apiPath === 'admin/events/update' && $method === 'POST') {
            (new AdminController())->updateEvent();
            exit;
        }
        if ($apiPath === 'admin/events/delete' && $method === 'POST') {
            (new AdminController())->deleteEvent();
            exit;
        }
        if ($apiPath === 'admin/cutoffs/create' && $method === 'POST') {
            (new AdminController())->createCutoff();
            exit;
        }
        if ($apiPath === 'admin/cutoffs/delete' && $method === 'POST') {
            (new AdminController())->deleteCutoff();
            exit;
        }
        if ($apiPath === 'admin/sources/create' && $method === 'POST') {
            (new AdminController())->createSource();
            exit;
        }
    }

    http_response_code(404);
    echo json_encode(['error' => 'API route not found']);
    exit;
}

// Serve Frontend Views
$frontendDir = $rootDir . '/frontend';

// Home
if ($requestUri === '/' || $requestUri === '/index.php') {
    require_once $frontendDir . '/views/home.php';
    exit;
}

// Admin Auth Views & Guard
if ($requestUri === '/admin/login') {
    if (AdminController::isAuthenticated()) {
        header('Location: /admin/dashboard');
        exit;
    }
    require_once $frontendDir . '/views/admin/login.php';
    exit;
}

if ($requestUri === '/admin/logout') {
    (new AdminController())->logout();
    exit;
}

// Admin Sub-Pages
if (str_starts_with($requestUri, '/admin')) {
    if (!AdminController::isAuthenticated()) {
        header('Location: /admin/login');
        exit;
    }

    if ($requestUri === '/admin' || $requestUri === '/admin/dashboard') {
        require_once $frontendDir . '/views/admin/dashboard.php';
        exit;
    }
    if ($requestUri === '/admin/recruitments' || $requestUri === '/admin/jobs') {
        require_once $frontendDir . '/views/admin/recruitments.php';
        exit;
    }
    if ($requestUri === '/admin/commissions') {
        require_once $frontendDir . '/views/admin/commissions.php';
        exit;
    }
    if ($requestUri === '/admin/exams') {
        require_once $frontendDir . '/views/admin/exams.php';
        exit;
    }
    if ($requestUri === '/admin/admit-cards') {
        require_once $frontendDir . '/views/admin/admit_cards.php';
        exit;
    }
    if ($requestUri === '/admin/results') {
        require_once $frontendDir . '/views/admin/results.php';
        exit;
    }
    if ($requestUri === '/admin/articles' || $requestUri === '/admin/guides') {
        require_once $frontendDir . '/views/admin/articles.php';
        exit;
    }
    if ($requestUri === '/admin/sources') {
        require_once $frontendDir . '/views/admin/sources.php';
        exit;
    }
    if ($requestUri === '/admin/automation') {
        require_once $frontendDir . '/views/admin/automation.php';
        exit;
    }
}

// Public Views
if ($requestUri === '/government-jobs' || $requestUri === '/jobs') {
    require_once $frontendDir . '/views/jobs_list.php';
    exit;
}

if (str_starts_with($requestUri, '/jobs/')) {
    $slug = substr($requestUri, strlen('/jobs/'));
    $_GET['slug'] = $slug;
    require_once $frontendDir . '/views/job_detail.php';
    exit;
}

// Dedicated Commissions Directory & Detail Dossiers
if ($requestUri === '/commissions') {
    require_once $frontendDir . '/views/commissions_list.php';
    exit;
}
if (str_starts_with($requestUri, '/commissions/')) {
    $slug = substr($requestUri, strlen('/commissions/'));
    $_GET['slug'] = $slug;
    require_once $frontendDir . '/views/commission_detail.php';
    exit;
}

// Dedicated Exam Hubs Directory & Detail Views
if ($requestUri === '/exams') {
    require_once $frontendDir . '/views/exams_list.php';
    exit;
}
if (str_starts_with($requestUri, '/exams/')) {
    $slug = substr($requestUri, strlen('/exams/'));
    $_GET['slug'] = $slug;
    require_once $frontendDir . '/views/exam_detail.php';
    exit;
}

// Dedicated Preparation Guides Directory & Detail Views
if ($requestUri === '/articles' || $requestUri === '/guides') {
    require_once $frontendDir . '/views/articles_list.php';
    exit;
}
if (str_starts_with($requestUri, '/articles/')) {
    $slug = substr($requestUri, strlen('/articles/'));
    $_GET['slug'] = $slug;
    require_once $frontendDir . '/views/article_detail.php';
    exit;
}

// Dedicated Admit Cards & Results Pages
if ($requestUri === '/admit-cards') {
    require_once $frontendDir . '/views/admit_cards.php';
    exit;
}
if ($requestUri === '/results') {
    require_once $frontendDir . '/views/results.php';
    exit;
}

// Sitemaps
if ($requestUri === '/sitemap.xml' || $requestUri === '/sitemap-index.xml') {
    $sm = $frontendDir . '/public/sitemap-index.xml';
    if (file_exists($sm)) {
        header("Content-Type: application/xml; charset=UTF-8");
        readfile($sm);
        exit;
    }
}
if ($requestUri === '/sitemap-jobs.xml') {
    $sm = $frontendDir . '/public/sitemap-jobs.xml';
    if (file_exists($sm)) {
        header("Content-Type: application/xml; charset=UTF-8");
        readfile($sm);
        exit;
    }
}
if ($requestUri === '/sitemap-exams.xml') {
    $sm = $frontendDir . '/public/sitemap-exams.xml';
    if (file_exists($sm)) {
        header("Content-Type: application/xml; charset=UTF-8");
        readfile($sm);
        exit;
    }
}
if ($requestUri === '/sitemap-articles.xml') {
    $sm = $frontendDir . '/public/sitemap-articles.xml';
    if (file_exists($sm)) {
        header("Content-Type: application/xml; charset=UTF-8");
        readfile($sm);
        exit;
    }
}

// Fallback to home
require_once $frontendDir . '/views/home.php';
