<?php
/**
 * JWT Auth Middleware
 *
 * Validates JWT tokens from the Authorization header.
 * Used as a guard layer for protected API endpoints.
 *
 * Usage:
 *   $payload = (new JwtAuthMiddleware())->handle();
 *   // $payload is the decoded JWT object, or exits with 401
 *
 * @package    rest-api-mvc-php
 * @subpackage app/Middleware
 * @author     rest-api-mvc-php team
 * @version    1.0
 */
class JwtAuthMiddleware
{
    /**
     * Validate the Authorization header and decode the JWT.
     *
     * Extracts the Bearer token from the Authorization header,
     * decodes it via jwtDecode(), and returns the payload.
     * Sends a 401 JSON response and halts execution on failure.
     *
     * @return object Decoded JWT payload (from jwtDecode)
     */
    public function handle()
    {
        $headers = $this->getAuthorizationHeader();
        ($headers === null) ? $this->unauthorized('Authorization header missing') : '';

        $token = $this->extractBearerToken($headers);
        ($token === null) ? $this->unauthorized('Invalid Authorization header format') : '';

        try {
            $payload = jwtDecode($token);
        } catch (Exception $ex) {
            $this->unauthorized('Invalid or expired token: ' . $ex->getMessage());
        }

        return $payload;
    }

    /**
     * Get the Authorization header from the request.
     *
     * @return string|null Header value or null if not present
     */
    private function getAuthorizationHeader()
    {
        if (isset($_SERVER['Authorization'])) {
            return trim($_SERVER['Authorization']);
        }
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return trim($_SERVER['HTTP_AUTHORIZATION']);
        }
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                return trim($headers['Authorization']);
            }
        }
        return null;
    }

    /**
     * Extract the Bearer token from the Authorization header.
     *
     * @param string $header Authorization header value
     *
     * @return string|null Token string or null if format is invalid
     */
    private function extractBearerToken(string $header)
    {
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    /**
     * Send 401 Unauthorized response and halt execution.
     *
     * @param string $message Error message
     */
    private function unauthorized(string $message)
    {
        HTTPStatus(401, 0, '/', $message, null);
        die();
    }
}