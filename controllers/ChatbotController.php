<?php
/**
 * Chatbot Controller
 * Handles chatbot API requests
 */

// Disable error display and start output buffering
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/env_loader.php';
} catch (Throwable $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Configuration error: ' . $e->getMessage()]);
    exit();
}

class ChatbotController {
    public function handle() {
        // Clear any output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Start fresh output buffer
        ob_start();
        
        // Set JSON header first
        header('Content-Type: application/json');
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is logged in
        if (empty($_SESSION['sessionID']) && empty($_SESSION['SessionID']) || empty($_SESSION['userID']) || empty($_SESSION['role'])) {
            ob_end_clean();
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized. Please log in.']);
            exit();
        }

        // Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit();
        }

        $hfApiKey = getenv('HUGGINGFACE_API_KEY') ?: $_ENV['HUGGINGFACE_API_KEY'] ?? '';

        if (empty($hfApiKey)) {
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Hugging Face API key not configured. Please set HUGGINGFACE_API_KEY in your .env file.']);
            exit();
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $userMessage = $input['message'] ?? '';

        if (empty($userMessage)) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['error' => 'Message is required']);
            exit();
        }

        $userMessage = trim($userMessage);
        $userMessage = htmlspecialchars($userMessage, ENT_QUOTES, 'UTF-8');

        // Different system prompts for admin vs user
        $role = $_SESSION['role'] ?? 'user';
        
        if ($role === 'admin') {
            $systemPrompt = "You are an expert administrative assistant for WAFRA, an event management and reservation platform. Your role is to help administrators efficiently manage the platform.

PLATFORM CAPABILITIES:
- User Management: View, edit, and manage user accounts (CIN, names, emails, roles, profile pictures)
- Event Management: Create, edit, and manage events (name, type, dates, location, description, QR codes)
- Reservation Management: View all reservations, edit reservation details, track event attendance
- Analytics: User statistics, registration charts, login activity, reservation trends
- System Settings: Configure site name, logo, contact email, maintenance mode, security settings, email templates
- Login Sessions: Monitor user login sessions, IP addresses, device information

YOUR EXPERTISE:
- Guide admins on how to use dashboard features effectively
- Explain how to create and manage events
- Help troubleshoot common issues (missing data, display problems, errors)
- Provide best practices for user management and event organization
- Explain reservation workflow and how to track attendance
- Assist with system configuration and settings
- Help interpret analytics and reports
- Suggest improvements for platform management

RESPONSE GUIDELINES:
- Be concise but comprehensive (2-4 sentences for simple questions, up to 6 for complex topics)
- Use clear, professional language
- Provide actionable advice when possible
- Reference specific dashboard sections when relevant (e.g., 'Go to Settings section', 'Check the Reservations tab')
- If you don't know something specific about the platform, suggest where to find it or how to investigate

STRICT RULES:
- ONLY answer questions related to WAFRA platform administration
- If asked about unrelated topics (coding, homework, general knowledge, etc.), politely redirect: \"I'm here to help with WAFRA platform administration. How can I assist you with managing users, events, reservations, or system settings?\"
- Never provide harmful, illegal, or personal information
- Be helpful, friendly, and solution-oriented";
        } else {
            $systemPrompt = "You are an expert assistant for a donation platform named wafra. Your task is to help users with questions about donations.

STRICT RULES:
- ONLY answer questions related to the donation platform (donations, payment methods, causes, campaigns, account management, etc.)
- If the user asks something unrelated (coding, schoolwork, personal advice, politics, general knowledge, etc.), reply EXACTLY with: \"I'm here only to help with questions about our donation platform. Please ask something related to donations.\"
- All answers must be short, clear, and professional (maximum 3-4 sentences)
- Never provide harmful, illegal, or personal information
- Never help with coding, homework, or off-topic questions
- Be friendly but focused on donation-related topics only";
        }

        $model = 'meta-llama/Llama-3.2-1B-Instruct';

        $apiUrl = 'https://router.huggingface.co/v1/chat/completions';
        $apiData = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt
                ],
                [
                    'role' => 'user',
                    'content' => $userMessage
                ]
            ],
            'max_tokens' => $role === 'admin' ? 250 : 150, // Allow more detailed responses for admin
            'temperature' => 0.7
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $hfApiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($curlError)) {
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to connect to AI service. Please try again later.']);
            exit();
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error'] ?? 'Unknown error occurred (HTTP ' . $httpCode . ')';
            
            if (strpos(strtolower($errorMessage), 'loading') !== false || $httpCode === 503) {
                ob_end_clean();
                http_response_code(503);
                echo json_encode([
                    'error' => 'The AI model is loading. Please wait 20-30 seconds and try again. This is normal on the free tier.',
                    'loading_error' => true
                ]);
                exit();
            }
            
            ob_end_clean();
            http_response_code($httpCode);
            echo json_encode(['error' => 'AI service error: ' . $errorMessage]);
            exit();
        }

        $responseData = json_decode($response, true);

        if ($responseData === null && json_last_error() !== JSON_ERROR_NONE) {
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to parse API response. The model may be loading. Please try again in 20-30 seconds.']);
            exit();
        }

        if (!isset($responseData['choices'][0]['message']['content'])) {
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Invalid response from AI service. The model may still be loading. Please wait 20-30 seconds and try again.']);
            exit();
        }

        $aiResponse = trim($responseData['choices'][0]['message']['content']);

        if (empty($aiResponse)) {
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['error' => 'Empty response from AI service']);
            exit();
        }

        // Clear buffer and send JSON response
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'response' => $aiResponse
        ]);
        exit();
    }
}

// Wrap controller call in try-catch to handle any errors
try {
    $controller = new ChatbotController();
    $controller->handle();
} catch (Throwable $e) {
    // Clean any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'An unexpected error occurred',
        'message' => $e->getMessage()
    ]);
    error_log('[ChatbotController] Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    exit();
}

