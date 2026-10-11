<?php

namespace App\Models;

use CodeIgniter\Model;

class ConversaModel extends Model
{
    protected $table = 'tchat_conversa';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'empresa_id',
        'tipo',
        'nome',
        'chave_privada',
        'id_grupo',
        'id_proprietario',
        'ultima_mensagem_id',
        'ultima_atividade_em',
        'grupo_privado',
        'descricao',
        'regras',
        'imagem',
        'criado_por',
        'criado_em',
        'atualizado_em',
        'ativo',
    ];
    protected $useTimestamps = false;

    /**
     * Retorna a lista de conversas do usuário autenticado dentro da empresa ativa
     */
    public function getConversasDoUsuario(int $userId, int $empresaId): array
    {
        $sql = "SELECT 
                    c.id,
                    c.empresa_id,
                    c.tipo,
                    c.nome AS grupo_nome,
                    c.imagem AS grupo_imagem,
                    c.descricao,
                    c.ultima_mensagem_id,
                    c.ultima_atividade_em,
                    c.criado_em,
                    p.fixada,
                    p.silenciado,
                    p.arquivado,
                    p.ultima_mensagem_lida_id,
                    m.mensagem AS ultima_mensagem_texto,
                    m.tipo AS ultima_mensagem_tipo,
                    m.criado_em AS ultima_mensagem_em,
                    m.id_usuario AS ultima_mensagem_usuario_id,
                    u_dest.id AS destinatario_id,
                    u_dest.name AS destinatario_nome,
                    u_dest.phone AS destinatario_telefone,
                    u_dest.avatar_url AS destinatario_avatar,
                    (
                        SELECT COUNT(msg.id) 
                        FROM tchat_mensagem msg 
                        WHERE msg.id_conversa = c.id 
                          AND msg.id > p.ultima_mensagem_lida_id 
                          AND msg.id_usuario != ?
                    ) AS nao_lidas
                FROM tchat_conversa c
                INNER JOIN tchat_participante p ON p.id_conversa = c.id AND p.id_usuario = ? AND p.ativo = true
                LEFT JOIN tchat_mensagem m ON m.id = c.ultima_mensagem_id
                LEFT JOIN tchat_participante p2 ON p2.id_conversa = c.id AND p2.id_usuario != ? AND p2.ativo = true AND c.tipo = 'P'
                LEFT JOIN users u_dest ON u_dest.id = p2.id_usuario
                WHERE c.empresa_id = ? AND c.ativo = true
                ORDER BY p.fixada DESC, COALESCE(c.ultima_atividade_em, c.criado_em) DESC";

        $query = $this->db->query($sql, [$userId, $userId, $userId, $empresaId]);
        $rows = $query->getResultArray();

        $result = [];
        foreach ($rows as $row) {
            $isPrivate = ($row['tipo'] === 'P');

            $result[] = [
                'id'                  => (int) $row['id'],
                'empresa_id'          => (int) $row['empresa_id'],
                'tipo'                => $row['tipo'],
                'nome'                => $isPrivate ? ($row['destinatario_nome'] ?? 'Colaborador') : ($row['grupo_nome'] ?? 'Grupo'),
                'imagem'              => $isPrivate ? $row['destinatario_avatar'] : $row['grupo_imagem'],
                'descricao'           => $row['descricao'],
                'fixada'              => in_array($row['fixada'], [true, 1, 't', 'true', '1'], true),
                'silenciado'          => in_array($row['silenciado'], [true, 1, 't', 'true', '1'], true),
                'arquivado'           => in_array($row['arquivado'], [true, 1, 't', 'true', '1'], true),
                'nao_lidas'           => (int) $row['nao_lidas'],
                'ultima_atividade_em' => $row['ultima_atividade_em'] ?? $row['criado_em'],
                'destinatario'        => $isPrivate && !empty($row['destinatario_id']) ? [
                    'id'       => (int) $row['destinatario_id'],
                    'nome'     => $row['destinatario_nome'],
                    'telefone' => $row['destinatario_telefone'],
                    'avatar'   => $row['destinatario_avatar'],
                ] : null,
                'ultima_mensagem'     => !empty($row['ultima_mensagem_id']) ? [
                    'id'              => (int) $row['ultima_mensagem_id'],
                    'texto'           => $row['ultima_mensagem_texto'],
                    'tipo'            => $row['ultima_mensagem_tipo'],
                    'criado_em'       => $row['ultima_mensagem_em'],
                    'enviado_por_mim' => ((int) $row['ultima_mensagem_usuario_id'] === $userId),
                ] : null,
            ];
        }

        return $result;
    }

    /**
     * Obtém ou cria uma conversa privada (1-a-1) isolada na empresa
     */
    public function getOrCreatePrivada(int $empresaId, int $user1Id, int $destinatarioId): array
    {
        $chave = min($user1Id, $destinatarioId) . '_' . max($user1Id, $destinatarioId);

        $sql = "SELECT id, empresa_id, tipo FROM tchat_conversa 
                WHERE empresa_id = ? AND chave_privada = ? AND ativo = true 
                LIMIT 1";

        $query = $this->db->query($sql, [$empresaId, $chave]);
        $conversa = $query->getRowArray();

        if ($conversa) {
            return [
                'id'         => (int) $conversa['id'],
                'empresa_id' => (int) $conversa['empresa_id'],
                'tipo'       => $conversa['tipo'],
                'recem_criada' => false,
            ];
        }

        // Se não existe, cria a conversa
        $sqlInsert = "INSERT INTO tchat_conversa (empresa_id, tipo, chave_privada, criado_por, criado_em, atualizado_em, ativo)
                      VALUES (?, 'P', ?, ?, NOW(), NOW(), true)
                      RETURNING id";

        $insertQuery = $this->db->query($sqlInsert, [$empresaId, $chave, $user1Id]);
        $newRow = $insertQuery->getRowArray();
        $conversaId = (int) $newRow['id'];

        // Insere ambos os participantes
        $sqlPart = "INSERT INTO tchat_participante (id_conversa, id_usuario, entrou_em, ativo, atualizado_em)
                    VALUES (?, ?, NOW(), true, NOW())
                    ON CONFLICT (id_conversa, id_usuario) DO UPDATE SET ativo = true";

        $this->db->query($sqlPart, [$conversaId, $user1Id]);
        $this->db->query($sqlPart, [$conversaId, $destinatarioId]);

        return [
            'id'           => $conversaId,
            'empresa_id'   => $empresaId,
            'tipo'         => 'P',
            'recem_criada' => true,
        ];
    }

    /**
     * Valida se a conversa pertence à empresa especificada
     */
    public function pertenceAEmpresa(int $conversaId, int $empresaId): bool
    {
        $sql = "SELECT 1 FROM tchat_conversa WHERE id = ? AND empresa_id = ? AND ativo = true LIMIT 1";
        $query = $this->db->query($sql, [$conversaId, $empresaId]);
        return $query->getNumRows() > 0;
    }

    /**
     * Valida se o usuário é participante ativo da conversa
     */
    public function isParticipante(int $conversaId, int $userId): bool
    {
        $sql = "SELECT 1 FROM tchat_participante WHERE id_conversa = ? AND id_usuario = ? AND ativo = true LIMIT 1";
        $query = $this->db->query($sql, [$conversaId, $userId]);
        return $query->getNumRows() > 0;
    }

    /**
     * Marca todas as mensagens da conversa como lidas para o usuário (zera contador)
     */
    public function marcarComoLida(int $conversaId, int $userId): bool
    {
        $sql = "UPDATE tchat_participante 
                SET ultima_mensagem_lida_id = COALESCE((SELECT MAX(id) FROM tchat_mensagem WHERE id_conversa = ?), 0),
                    atualizado_em = NOW()
                WHERE id_conversa = ? AND id_usuario = ?";

        $this->db->query($sql, [$conversaId, $conversaId, $userId]);
        return $this->db->affectedRows() > 0;
    }

    /**
     * Cria um novo grupo corporativo com participantes da mesma empresa
     */
    public function criarGrupo(int $empresaId, int $criadorId, string $nome, ?string $descricao = null, ?string $imagem = null, array $participantesIds = []): int
    {
        $sqlConversa = "INSERT INTO tchat_conversa (empresa_id, tipo, nome, descricao, imagem, id_proprietario, criado_por, criado_em, atualizado_em, ativo)
                        VALUES (?, 'G', ?, ?, ?, ?, ?, NOW(), NOW(), true)
                        RETURNING id";

        $query = $this->db->query($sqlConversa, [
            $empresaId,
            $nome,
            $descricao,
            $imagem,
            $criadorId,
            $criadorId,
        ]);

        $row = $query->getRowArray();
        $conversaId = (int) $row['id'];

        // Insere o criador como administrador do grupo
        $sqlCriador = "INSERT INTO tchat_participante (id_conversa, id_usuario, administrador, entrou_em, ativo, atualizado_em)
                       VALUES (?, ?, true, NOW(), true, NOW())";
        $this->db->query($sqlCriador, [$conversaId, $criadorId]);

        // Insere os demais participantes
        $sqlMembro = "INSERT INTO tchat_participante (id_conversa, id_usuario, administrador, entrou_em, ativo, atualizado_em)
                      VALUES (?, ?, false, NOW(), true, NOW())
                      ON CONFLICT (id_conversa, id_usuario) DO UPDATE SET ativo = true";

        foreach ($participantesIds as $pId) {
            $pId = (int) $pId;
            if ($pId !== $criadorId && $pId > 0) {
                $this->db->query($sqlMembro, [$conversaId, $pId]);
            }
        }

        return $conversaId;
    }
}