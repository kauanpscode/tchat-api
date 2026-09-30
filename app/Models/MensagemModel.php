<?php

namespace App\Models;

use CodeIgniter\Model;

class MensagemModel extends Model
{
    protected $table = 'tchat_mensagem';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'id_conversa', 'id_usuario', 'tipo', 'mensagem', 
        'respondendo_id', 'client_id', 'criado_em', 'editado_em', 'excluido_em'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'criado_em';
    protected $updatedField  = 'editado_em';

    /**
     * Envia uma nova mensagem na conversa.
     */
    public function enviarMensagem(array $data): int
    {
        $sql = "INSERT INTO tchat_mensagem (id_conversa, id_usuario, tipo, mensagem, respondendo_id, client_id, criado_em) 
                VALUES (?, ?, ?, ?, ?, ?, NOW()) RETURNING id";
        
        $query = $this->db->query($sql, [
            $data['id_conversa'],
            $data['id_usuario'],
            $data['tipo'] ?? 'texto',
            $data['mensagem'],
            $data['respondendo_id'] ?? null,
            $data['client_id'] ?? null
        ]);

        $row = $query->getRowArray();
        $mensagemId = (int) $row['id'];

        // Atualiza a conversa com a ultima mensagem
        $updateSql = "UPDATE tchat_conversa 
                      SET ultima_mensagem_id = ?, ultima_atividade_em = NOW() 
                      WHERE id = ?";
        $this->db->query($updateSql, [$mensagemId, $data['id_conversa']]);

        return $mensagemId;
    }

    /**
     * Salva os metadados do anexo no banco
     */
    public function salvarAnexo(int $mensagemId, array $uploadData, string $tipo): void
    {
        $sql = "INSERT INTO tchat_anexo (id_mensagem, tipo, nome_original, caminho, tamanho, criado_em) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $this->db->query($sql, [
            $mensagemId,
            $tipo,
            $uploadData['name'],
            $uploadData['url'], // caminho público
            $uploadData['size']
        ]);
    }

    /**
     * Busca o histórico de mensagens de uma conversa (com paginação simples e anexos).
     */
    public function getHistorico(int $conversaId, int $limit = 40, int $offset = 0): array
    {
        $sql = "SELECT m.id, m.id_usuario, m.tipo, m.mensagem, m.criado_em, m.respondendo_id,
                       u.name as usuario_nome, u.avatar_url as usuario_avatar,
                       r.mensagem as mensagem_respondida,
                       a.caminho as anexo_url, a.nome_original as anexo_nome, a.tamanho as anexo_tamanho
                FROM tchat_mensagem m
                JOIN users u ON m.id_usuario = u.id
                LEFT JOIN tchat_mensagem r ON m.respondendo_id = r.id
                LEFT JOIN tchat_anexo a ON m.id = a.id_mensagem
                WHERE m.id_conversa = ?
                ORDER BY m.criado_em DESC
                LIMIT ? OFFSET ?";
        
        $query = $this->db->query($sql, [$conversaId, $limit, $offset]);
        return $query->getResultArray();
    }
    
    /**
     * Pega os IDs de outros participantes da conversa (para notificação Push).
     */
    public function getOutrosParticipantesIds(int $conversaId, int $userId): array
    {
        $sql = "SELECT id_usuario FROM tchat_participante WHERE id_conversa = ? AND id_usuario != ?";
        $query = $this->db->query($sql, [$conversaId, $userId]);
        
        $ids = [];
        foreach($query->getResultArray() as $row) {
            $ids[] = (int) $row['id_usuario'];
        }
        return $ids;
    }
}
