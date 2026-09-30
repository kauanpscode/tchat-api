<?php

namespace App\Models;

use CodeIgniter\Model;

class ConversaModel extends Model
{
    protected $table = 'tchat_conversa';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'tipo', 'nome', 'chave_privada', 'imagem', 
        'ultima_mensagem_id', 'ultima_atividade_em', 
        'criado_por', 'ativo', 'criado_em', 'atualizado_em'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'criado_em';
    protected $updatedField  = 'atualizado_em';

    /**
     * Busca ou cria uma conversa privada entre dois usuários.
     */
    public function getOrCreatePrivada(int $userId, int $targetUserId): int
    {
        // Chave única ordenando os IDs para evitar duplicidade (ex: '2_5' e '5_2' viram '2_5')
        $minId = min($userId, $targetUserId);
        $maxId = max($userId, $targetUserId);
        $chavePrivada = "{$minId}_{$maxId}";

        // Tenta encontrar a conversa
        $conversa = $this->where('chave_privada', $chavePrivada)
                         ->where('tipo', 'P')
                         ->first();

        if ($conversa) {
            return (int) $conversa['id'];
        }

        // Se não existir, cria a conversa
        $data = [
            'tipo' => 'P',
            'chave_privada' => $chavePrivada,
            'criado_por' => $userId
        ];
        
        // No Postgres com CodeIgniter 4, o insert retorna a primary key se configurada no driver, ou precisamos garantir.
        // O $this->insert pode não retornar o ID no Postgres em versões antigas, mas vamos usar o insert do builder:
        $sql = "INSERT INTO tchat_conversa (tipo, chave_privada, criado_por, criado_em, atualizado_em) 
                VALUES (?, ?, ?, NOW(), NOW()) RETURNING id";
        
        $query = $this->db->query($sql, [$data['tipo'], $data['chave_privada'], $data['criado_por']]);
        $row = $query->getRowArray();
        $conversaId = (int) $row['id'];

        // Adiciona os dois participantes
        $this->addParticipante($conversaId, $userId);
        $this->addParticipante($conversaId, $targetUserId);

        return $conversaId;
    }

    /**
     * Adiciona um usuário a uma conversa.
     */
    public function addParticipante(int $conversaId, int $userId): void
    {
        $sql = "INSERT INTO tchat_participante (id_conversa, id_usuario, entrou_em) 
                VALUES (?, ?, NOW()) 
                ON CONFLICT (id_conversa, id_usuario) DO NOTHING";
        $this->db->query($sql, [$conversaId, $userId]);
    }

    /**
     * Busca todas as conversas de um usuário logado.
     */
    public function getUserConversas(int $userId): array
    {
        $sql = "SELECT c.id, c.tipo, c.nome, c.imagem, c.ultima_atividade_em,
                       p.ultima_mensagem_lida_id,
                       m.mensagem as ultima_mensagem_texto,
                       m.criado_em as ultima_mensagem_data,
                       u.id as outro_usuario_id, u.name as outro_usuario_nome, u.avatar_url as outro_usuario_avatar
                FROM tchat_conversa c
                JOIN tchat_participante p ON c.id = p.id_conversa
                LEFT JOIN tchat_mensagem m ON c.ultima_mensagem_id = m.id
                -- Busca os dados do outro participante se for conversa privada
                LEFT JOIN tchat_participante p2 ON c.id = p2.id_conversa AND p2.id_usuario != ?
                LEFT JOIN users u ON p2.id_usuario = u.id
                WHERE p.id_usuario = ? AND p.ativo = TRUE
                ORDER BY c.ultima_atividade_em DESC";

        $query = $this->db->query($sql, [$userId, $userId]);
        return $query->getResultArray();
    }
}