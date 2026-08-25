<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Config\Services;
use Exception;

class AuthFilter implements FilterInterface
{
    /**
     * Valida o token JWT no cabeçalho da requisição antes de acessar a rota
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getServer('HTTP_AUTHORIZATION');

        if (!$authHeader) {
            return Services::response()
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON([
                    'status'  => 401,
                    'message' => 'Token de autenticação não fornecido.',
                ]);
        }

        $arr = explode(' ', $authHeader);
        $token = $arr[1] ?? null;

        if (!$token || strtolower($arr[0]) !== 'bearer') {
            return Services::response()
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON([
                    'status'  => 401,
                    'message' => 'Formato de cabeçalho inválido. Use: Bearer <token>',
                ]);
        }

        try {
            $key = getenv('JWT_SECRET_KEY');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));

            // Injeta os dados do usuário decodificados no objeto $request
            $request->user = $decoded->data;
        } catch (Exception $e) {
            return Services::response()
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON([
                    'status'  => 401,
                    'message' => 'Token inválido ou expirado.',
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}