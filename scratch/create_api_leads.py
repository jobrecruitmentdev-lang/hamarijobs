import os

api_leads_file = r"C:\hk\prmarketing\backend\api\leads.php"

content = """<?php
/**
 * Client Leads Ingestion API Endpoint
 * PR Marketing Ventures Enterprise Backend
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../repositories/LeadRepository.php';

$leadRepo = new LeadRepository();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: $_POST;

    $fullName = trim($input['fullName'] ?? $input['full_name'] ?? '');
    $phone = trim($input['phoneNumber'] ?? $input['phone_number'] ?? '');

    if (empty($fullName) || empty($phone)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Full Name and Phone Number are required fields.'
        ]);
        exit;
    }

    try {
        $leadId = $leadRepo->create($input);
        echo json_encode([
            'success' => true,
            'message' => 'Client lead successfully captured and synced with CRM database.',
            'lead_id' => $leadId
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to store lead: ' . $e->getMessage()
        ]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
"""

with open(api_leads_file, "w", encoding="utf-8") as f:
    f.write(content.strip())

print(f"Created {api_leads_file} successfully!")
