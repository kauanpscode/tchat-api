<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Config\Services;
use Config\Database;
use Exception;

/**
 * Filtro de Autenticação JWT e Validação de Multi-tenancy Corporativo
 * 
 * Garante que:
 * 1. O Token JWT é autêntico e válido;
 * 2. A empresa vinculada ao token existe e está ativa;
 * 3. O colaborador continua ativo na empresa em tempo real (revogação instantânea);
 * 4. Injeta os objetos $request->user e $request->empresa no contexto da requisição.
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getServer('HTTP_AUTHORIZATION');

        if (!$authHeader) {
            return Services::response()
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON([
                    'status'  => 401,
                    'message' => 'Token de autenticação não fornecido no cabeçalho.',
                ]);
        }

        $arr = explode(' ', $authHeader);
        $token = $arr[1] ?? null;

        if (!$token || strtolower($arr[0]) !== 'bearer') {
            return Services::response()
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON([
                    'status'  => 401,
                    'message' => 'Formato de cabeçalho de autenticação inválido. Utilize: Bearer <token>',
                ]);
        }

        try {
            $key = getenv('JWT_SECRET_KEY');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            $tokenData = $decoded->data;

            $userId = (int) ($tokenData->id ?? 0);
            $empresaId = (int) ($tokenData->empresa_id ?? 0);

            if (!$userId || !$empresaId) {
                return Services::response()
                    ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                    ->setJSON([
                        'status'  => 401,
                        'message' => 'Token sem identificação corporativa válida.',
                    ]);
            }

            // Validação em tempo real no banco de dados com SQL puro
            $db = Database::connect();
            $sql = "SELECT eu.cargo, eu.status AS vinculo_status,
                           e.id AS empresa_id, e.nome AS empresa_nome, e.ativo AS empresa_ativa
                    FROM empresa_usuarios eu
                    INNER JOIN empresas e ON e.id = eu.empresa_id
                    WHERE eu.usuario_id = ? AND eu.empresa_id = ?
                    LIMIT 1";

            $query = $db->query($sql, [$userId, $empresaId]);
            $vinculo = $query->getRowArray();

            if (!$vinculo) {
                return Services::response()
                    ->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)
                    ->setJSON([
                        'status'  => 403,
                        'message' => 'Você não possui vínculo com esta empresa.',
                    ]);
            }

            if (!$vinculo['empresa_ativa']) {
                return Services::response()
                    ->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)
                    ->setJSON([
                        'status'  => 403,
                        'message' => 'O ambiente corporativo desta empresa está temporariamente inativo.',
                    ]);
            }

            if ($vinculo['vinculo_status'] !== 'ativo') {
                return Services::response()
                    ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                    ->setJSON([
                        'status'  => 401,
                        'message' => 'Seu acesso a esta empresa foi desativado ou suspenso.',
                    ]);
            }

            // Injeta dados autenticados no request para consumo nos Controllers
            $request->user = (object) [
                'id'         => $userId,
                'name'       => $tokenData->name ?? '',
                'phone'      => $tokenData->phone ?? '',
                'empresa_id' => $empresaId,
                'cargo'      => $vinculo['cargo'],
            ];

            $request->empresa = (object) [
                'id'   => (int) $vinculo['empresa_id'],
                'nome' => $vinculo['empresa_nome'],
            ];

        } catch (Exception $e) {
            return Services::response()
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON([
                    'status'  => 401,
                    'message' => 'Sessão expirada ou token de acesso inválido.',
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}