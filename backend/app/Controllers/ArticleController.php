<?php
namespace App\Controllers;

use App\Database;
use PDO;

class ArticleController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function listArticles(): void {
        $type = $_GET['type'] ?? '';
        $limit = min(50, max(5, intval($_GET['limit'] ?? 20)));

        $query = "SELECT id, article_uuid, title, slug, article_type, excerpt, reading_time_minutes, quality_score, published_at FROM articles WHERE status = 'Published'";
        $params = [];

        if (!empty($type)) {
            $query .= " AND article_type = ?";
            $params[] = $type;
        }

        $query .= " ORDER BY published_at DESC LIMIT {$limit}";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $articles = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'total' => count($articles),
            'data' => $articles
        ]);
    }

    public function getArticleBySlug(string $slug): void {
        $stmt = $this->db->prepare("SELECT * FROM articles WHERE slug = ? AND status = 'Published' LIMIT 1");
        $stmt->execute([$slug]);
        $article = $stmt->fetch();

        if (!$article) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Article not found']);
            return;
        }

        // Increment view count
        $this->db->prepare("UPDATE articles SET view_count = view_count + 1 WHERE id = ?")->execute([$article['id']]);

        // Fetch related recruitment if attached
        $rec = null;
        if (!empty($article['recruitment_id'])) {
            $recStmt = $this->db->prepare("SELECT id, title, slug, organization_name, total_vacancies, official_apply_url FROM recruitments WHERE id = ? LIMIT 1");
            $recStmt->execute([$article['recruitment_id']]);
            $rec = $recStmt->fetch();
        }

        echo json_encode([
            'success' => true,
            'article' => $article,
            'related_recruitment' => $rec
        ]);
    }
}
