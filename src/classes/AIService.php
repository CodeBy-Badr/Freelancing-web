<?php
/**
 * AIService – Bridges the application to Gemini / OpenAI APIs via cURL.
 * All API keys stay server-side; they are never exposed to the client.
 */

require_once __DIR__ . '/../../config/config.php';

class AIService
{
    private string $apiKey;
    private string $provider; // 'gemini' | 'openai'

    public function __construct()
    {
        // Auto-select provider based on which key is populated
        if (!empty(GEMINI_API_KEY) && GEMINI_API_KEY !== 'YOUR_GEMINI_API_KEY_HERE') {
            $this->apiKey   = GEMINI_API_KEY;
            $this->provider = 'gemini';
        } elseif (!empty(OPENAI_API_KEY)) {
            $this->apiKey   = OPENAI_API_KEY;
            $this->provider = 'openai';
        } else {
            throw new RuntimeException('No AI API key configured. Please set GEMINI_API_KEY or OPENAI_API_KEY in config/config.php.');
        }
    }

    // ─── Public Interface ─────────────────────────────────────────────────────

    /**
     * Review code and return structured markdown feedback.
     *
     * @param  string $code     The raw code snippet pasted by the user
     * @param  string $language Language hint (html|css|javascript|php|typescript)
     * @return string           Markdown-formatted AI response
     */
    public function reviewCode(string $code, string $language = 'javascript'): string
    {
        $systemPrompt = $this->getCodeReviewPrompt($language);
        $userMessage  = "Please review the following {$language} code:\n\n```{$language}\n{$code}\n```";

        return $this->chat([
            ['role' => 'user', 'content' => $systemPrompt . "\n\n" . $userMessage]
        ]);
    }

    /**
     * Continue a mock interview conversation.
     *
     * @param  array  $history Array of ['role' => 'user'|'assistant', 'content' => string]
     * @param  string $devRole The interview role (e.g. "Frontend Developer")
     * @return string          AI response text
     */
    public function conductInterview(array $history, string $devRole): string
    {
        $systemPrompt = $this->getInterviewPrompt($devRole);

        // Prepend system context to the first user turn for Gemini compatibility
        if (!empty($history) && $history[0]['role'] === 'user') {
            $history[0]['content'] = $systemPrompt . "\n\n" . $history[0]['content'];
        } elseif (empty($history)) {
            $history[] = ['role' => 'user', 'content' => $systemPrompt . "\n\nPlease start the interview."];
        }

        return $this->chat($history);
    }

    // ─── Provider Router ──────────────────────────────────────────────────────

    private function chat(array $messages): string
    {
        return $this->provider === 'gemini'
            ? $this->callGemini($messages)
            : $this->callOpenAI($messages);
    }

    // ─── Gemini ───────────────────────────────────────────────────────────────

    private function callGemini(array $messages): string
    {
        // Gemini uses 'parts' with 'text', roles are 'user' and 'model'
        $contents = [];
        foreach ($messages as $msg) {
            $contents[] = [
                'role'  => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['content']]]
            ];
        }

        $payload = json_encode([
            'contents'         => $contents,
            'generationConfig' => [
                'temperature'     => 0.7,
                'maxOutputTokens' => 4096,
                'topP'            => 0.95,
            ],
            'safetySettings'   => [
                ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
            ],
        ]);

        $url      = GEMINI_API_URL . '?key=' . $this->apiKey;
        $response = $this->curlPost($url, $payload, ['Content-Type: application/json']);
        $data     = json_decode($response, true);

        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }

        error_log('Gemini API unexpected response: ' . $response);
        throw new RuntimeException('AI service returned an unexpected response. Please try again.');
    }

    // ─── OpenAI ───────────────────────────────────────────────────────────────

    private function callOpenAI(array $messages): string
    {
        $payload = json_encode([
            'model'       => OPENAI_MODEL,
            'messages'    => $messages,
            'temperature' => 0.7,
            'max_tokens'  => 4096,
        ]);

        $headers  = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];
        $response = $this->curlPost(OPENAI_API_URL, $payload, $headers);
        $data     = json_decode($response, true);

        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }

        error_log('OpenAI API unexpected response: ' . $response);
        throw new RuntimeException('AI service returned an unexpected response. Please try again.');
    }

    // ─── cURL Helper ──────────────────────────────────────────────────────────

    private function curlPost(string $url, string $payload, array $headers): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log('cURL error: ' . $curlErr);
            throw new RuntimeException('Network error while contacting AI service: ' . $curlErr);
        }

        if ($httpCode !== 200) {
            error_log("AI API HTTP {$httpCode}: " . $response);
            throw new RuntimeException("AI service error (HTTP {$httpCode}). Please check your API key and try again.");
        }

        return $response;
    }

    // ─── System Prompts ───────────────────────────────────────────────────────

    private function getCodeReviewPrompt(string $language): string
    {
        return <<<PROMPT
You are an expert Senior Full-Stack Engineer and Code Auditor with 15+ years of experience.
Your task is to perform a thorough code review. Structure your response in clean Markdown with the following exact sections:

## 🔍 Performance Bottleneck Analysis
Identify performance issues, inefficiencies, redundant operations, or anti-patterns. Be specific about line-level concerns.

## 🔒 Security Audit
Highlight any XSS, injection, CSRF, or other security vulnerabilities. Explain the risk and severity.

## ✨ Refactored Code
Provide a clean, secure, production-ready refactored version of the code. Use modern best practices for {$language}.
Wrap in a fenced code block.

## 🎨 UI/UX Improvement Suggestions
If applicable, provide Tailwind CSS utility class recommendations and layout improvements.
Include before/after code snippets where relevant.

## 📊 Quality Score
Give an overall quality score out of 10 in a table format, with sub-scores for:
| Category | Score | Notes |
|----------|-------|-------|
| Performance | X/10 | ... |
| Security | X/10 | ... |
| Readability | X/10 | ... |
| Best Practices | X/10 | ... |
| **Overall** | **X/10** | |

Be constructive, educational, and concise.
PROMPT;
    }

    private function getInterviewPrompt(string $devRole): string
    {
        return <<<PROMPT
You are an expert technical interviewer conducting a professional mock interview for a "{$devRole}" position.

Rules you MUST follow:
1. Ask ONE focused technical question at a time.
2. After the candidate answers, evaluate their response with a score out of 10 and brief constructive feedback.
3. Then immediately ask the next question. Vary difficulty (easy → medium → hard).
4. Focus on these topic areas based on the role:
   - Frontend Developer: JavaScript closures, event loop, DOM, CSS specificity, React/Vue concepts
   - Backend Engineer: PHP OOP (interfaces, traits, abstract classes), MySQL indexes, query optimization, REST API design
   - Full-Stack Developer: Mix of all the above plus system design basics
5. After 5 questions, provide a comprehensive final evaluation:
   - Individual question scores in a Markdown table
   - Overall average score out of 10
   - Strengths identified
   - Areas for improvement
   - Learning resources recommendation

Format scores clearly: **Score: X/10**
Be encouraging but honest. Do not repeat questions.
Start by introducing yourself briefly and asking the first question.
PROMPT;
    }
}
