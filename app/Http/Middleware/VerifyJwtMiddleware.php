<?php

namespace App\Http\Middleware;

use App\Services\JwtUserSyncService;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifyJwtMiddleware
{
    public function __construct(
        private readonly JwtUserSyncService $jwtUserSync
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $uri = $request->getRequestUri();
        if (str_starts_with($uri, '/api/docs')
            || str_starts_with($uri, '/api/documentation')
            || str_starts_with($uri, '/docs')) {
            return $next($request);
        }

        $authHeader = $request->headers->get('authorization') ?: $request->header('Authorization');

        if (! $authHeader || ! str_starts_with(strtolower($authHeader), 'bearer ')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $token = trim(substr($authHeader, 7));
        $algo = env('JWT_ALGO', 'HS256');

        try {
            $payload = $this->decodeToken($token, $algo);

            if ($issuer = env('JWT_ISSUER')) {
                $tokenIssuer = is_object($payload) ? ($payload->iss ?? null) : ($payload['iss'] ?? null);
                if ($tokenIssuer && $tokenIssuer !== $issuer) {
                    return response()->json(['message' => 'Invalid token issuer'], 401);
                }
            }

            $request->attributes->set('jwt_payload', $payload);

            $sub = is_object($payload) ? ($payload->sub ?? null) : ($payload['sub'] ?? null);

            if ($sub !== null && $sub !== '') {
                $user = $this->jwtUserSync->syncFromPayload($payload);
                Auth::setUser($user);
                $request->setUserResolver(fn () => $user);
            }
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        return $next($request);
    }

    private function decodeToken(string $token, string $algo): object
    {
        if (strtoupper($algo) === 'RS256') {
            $pubPath = env('JWT_PUBLIC_KEY_PATH');
            $publicKey = null;

            if ($pubPath && is_readable($pubPath)) {
                $publicKey = file_get_contents($pubPath);
            } elseif (env('JWT_PUBLIC_KEY')) {
                $publicKey = env('JWT_PUBLIC_KEY');
            }

            if (! $publicKey) {
                throw new \RuntimeException('Server misconfigured: missing public key');
            }

            return JWT::decode($token, new Key($publicKey, 'RS256'));
        }

        $secret = env('SECRETJWT');
        if (! $secret) {
            throw new \RuntimeException('Server misconfigured');
        }

        return JWT::decode($token, new Key($secret, 'HS256'));
    }
}
