<?php
namespace App\Controllers;

use App\Database;
use PDO;

class ExamController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function listExams(): void {
        $stmt = $this->db->query("
            SELECT id, exam_uuid, name, short_name, slug, conducting_body, category, frequency, overview, is_active 
            FROM exams 
            WHERE is_active = 1 
            ORDER BY category ASC, name ASC
        ");
        $exams = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'total' => count($exams),
            'data' => $exams
        ]);
    }

    public function getExamBySlug(string $slug): void {
        $stmt = $this->db->prepare("SELECT * FROM exams WHERE slug = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$slug]);
        $exam = $stmt->fetch();

        if (!$exam) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Exam hub not found']);
            return;
        }

        $examId = $exam['id'];

        // Fetch Phases
        $phaseStmt = $this->db->prepare("SELECT * FROM exam_phases WHERE exam_id = ? ORDER BY phase_order ASC");
        $phaseStmt->execute([$examId]);
        $phases = $phaseStmt->fetchAll();

        // Fetch Patterns
        $patStmt = $this->db->prepare("SELECT * FROM exam_patterns WHERE exam_id = ?");
        $patStmt->execute([$examId]);
        $patterns = $patStmt->fetchAll();

        // Fetch Syllabus
        $sylStmt = $this->db->prepare("SELECT * FROM exam_syllabus WHERE exam_id = ? ORDER BY weightage_percentage DESC");
        $sylStmt->execute([$examId]);
        $syllabus = $sylStmt->fetchAll();

        // Fetch Cutoffs
        $cutStmt = $this->db->prepare("SELECT * FROM cutoff_records WHERE exam_id = ? ORDER BY year DESC, category ASC");
        $cutStmt->execute([$examId]);
        $cutoffs = $cutStmt->fetchAll();

        // Fetch Active Related Recruitments
        $recStmt = $this->db->prepare("
            SELECT id, title, slug, organization_name, year, total_vacancies, status, created_at 
            FROM recruitments 
            WHERE organization_name LIKE ? OR title LIKE ?
            ORDER BY year DESC 
            LIMIT 5
        ");
        $recStmt->execute(["%{$exam['short_name']}%", "%{$exam['short_name']}%"]);
        $recruitments = $recStmt->fetchAll();

        echo json_encode([
            'success' => true,
            'exam' => $exam,
            'phases' => $phases,
            'patterns' => $patterns,
            'syllabus' => $syllabus,
            'cutoffs' => $cutoffs,
            'active_recruitments' => $recruitments
        ]);
    }
}
