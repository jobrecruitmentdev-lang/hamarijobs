import os

public_api_leads = r"C:\hk\prmarketing\website\public\api\leads.php"
backend_api_leads = r"C:\hk\prmarketing\backend\api\leads.php"

content = """<?php
/**
 * PR Marketing Ventures — Real-Time Lead Capture API (MySQL Enterprise Engine)
 * Captures tool inquiries directly into pr_client_leads table.
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

// 1. Parse JSON Payload
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?: $_POST;

// 2. Validate Required Fields
$fullName      = trim($data['fullName'] ?? $data['name'] ?? '');
$phone         = trim($data['phoneNumber'] ?? $data['phone'] ?? $data['whatsapp'] ?? '');
$whatsapp      = trim($data['whatsappNumber'] ?? $phone);
$websiteName   = trim($data['websiteName'] ?? $data['businessName'] ?? $data['company'] ?? '');
$websiteUrl    = trim($data['websiteUrl'] ?? $data['url'] ?? $data['domain'] ?? '');
$businessStage = trim($data['businessStage'] ?? $data['stage'] ?? 'General Inquiry');
$toolUsed      = trim($data['toolUsed'] ?? $data['tool'] ?? 'Marketing Tool');

if (empty($fullName) || empty($phone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Full Name and Phone/WhatsApp number are required.']);
    exit;
}

$ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// 3. Connect to Database and Store Lead
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/repositories/LeadRepository.php';

try {
    $leadRepo = new LeadRepository();
    $leadId = $leadRepo->create([
        'fullName'       => $fullName,
        'phoneNumber'    => $phone,
        'whatsappNumber' => $whatsapp,
        'websiteName'    => $websiteName,
        'websiteUrl'     => $websiteUrl,
        'businessStage'  => $businessStage,
        'toolUsed'       => $toolUsed
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Lead captured and stored in database successfully.',
        'data' => [
            'id'       => $leadId,
            'fullName' => $fullName,
            'toolUsed' => $toolUsed,
            'status'   => 'New Lead'
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database storage error: ' . $e->getMessage()
    ]);
}
"""

os.makedirs(os.path.dirname(public_api_leads), exist_ok=True)
with open(public_api_leads, "w", encoding="utf-8") as f:
    f.write(content.strip())

os.makedirs(os.path.dirname(backend_api_leads), exist_ok=True)
with open(backend_api_leads, "w", encoding="utf-8") as f:
    f.write(content.strip())

print("Updated both public and backend api/leads.php!")
