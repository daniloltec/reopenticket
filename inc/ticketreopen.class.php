<?php

/**
 * Reabre um chamado solucionado ou fechado e registra o motivo no histórico.
 */
class PluginReopenticketTicketreopen
{
    public static $rightname = 'plugin_reopenticket';

    public static function canReopen(Ticket $ticket): bool
    {
        if (!$ticket->getID() || !empty($ticket->fields['is_deleted'])) {
            return false;
        }

        if (!$ticket->can((int) $ticket->getID(), READ)) {
            return false;
        }

        $status = (int) $ticket->fields['status'];
        if ($status === CommonITILObject::SOLVED && !PluginReopenticketConfig::enabled('reopen_solved', true)) {
            return false;
        }
        if ($status === CommonITILObject::CLOSED && !PluginReopenticketConfig::enabled('reopen_closed', true)) {
            return false;
        }
        if (!in_array($status, [CommonITILObject::SOLVED, CommonITILObject::CLOSED], true)) {
            return false;
        }

        if (!self::withinDeadline($ticket)) {
            return false;
        }

        return self::isAllowedActor($ticket);
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public static function reopen(int $tickets_id, string $reason): array
    {
        $ticket = new Ticket();
        if (!$ticket->getFromDB($tickets_id) || !empty($ticket->fields['is_deleted'])) {
            return ['ok' => false, 'message' => 'Chamado não encontrado.'];
        }

        if (!self::canReopen($ticket)) {
            return ['ok' => false, 'message' => 'Você não pode reabrir este chamado.'];
        }

        $reason = trim($reason);
        if ($reason === '') {
            if (PluginReopenticketConfig::enabled('require_reason', true)) {
                return ['ok' => false, 'message' => 'Informe o motivo da reabertura.'];
            }
            $reason = 'Chamado reaberto sem motivo informado.';
        }

        $target = self::targetStatus();
        $fromStatus = (int) $ticket->fields['status'];
        $content = self::followupContent($reason);
        $followupId = 0;

        self::withReopenRights($fromStatus, $target, function () use ($tickets_id, $content, $target, &$followupId) {
            $followup = new ITILFollowup();
            $followupId = (int) $followup->add([
                'itemtype'   => Ticket::class,
                'items_id'   => $tickets_id,
                'content'    => $content,
                'is_private' => 0,
                'add_reopen' => 1,
                '_status'    => $target,
            ]);

            $reloaded = new Ticket();
            if (
                $followupId > 0
                && $reloaded->getFromDB($tickets_id)
                && (int) $reloaded->fields['status'] !== $target
            ) {
                $reloaded->update([
                    'id'     => $tickets_id,
                    'status' => $target,
                ]);
            }
        });

        if ($followupId <= 0) {
            return ['ok' => false, 'message' => 'Não foi possível registrar a reabertura.'];
        }

        $ticket->getFromDB($tickets_id);
        if ((int) $ticket->fields['status'] === $target) {
            return ['ok' => true, 'message' => 'Chamado reaberto.'];
        }

        return ['ok' => false, 'message' => 'O motivo foi registrado, mas o status do chamado não mudou.'];
    }

    private static function isAllowedActor(Ticket $ticket): bool
    {
        $ticket->loadActors();
        $userId = (int) Session::getLoginUserID();

        if ($userId > 0 && $ticket->isUser(CommonITILActor::REQUESTER, $userId)) {
            return true;
        }

        foreach ($_SESSION['glpigroups'] ?? [] as $groupId) {
            if ($ticket->isGroup(CommonITILActor::REQUESTER, (int) $groupId)) {
                return true;
            }
        }

        return Session::haveRight(self::$rightname, READ);
    }

    private static function withinDeadline(Ticket $ticket): bool
    {
        $days = PluginReopenticketConfig::intValue('reopen_days', 0);
        if ($days <= 0) {
            return true;
        }

        $status = (int) $ticket->fields['status'];
        $date = $status === CommonITILObject::CLOSED
            ? ($ticket->fields['closedate'] ?? '')
            : ($ticket->fields['solvedate'] ?? '');

        if ($date === '' || $date === null || $date === 'NULL') {
            $date = $ticket->fields['date_mod'] ?? '';
        }

        $base = strtotime((string) $date);
        if ($base === false) {
            return true;
        }

        return time() <= strtotime('+' . $days . ' days', $base);
    }

    private static function targetStatus(): int
    {
        $target = PluginReopenticketConfig::intValue('target_status', CommonITILObject::ASSIGNED);
        if (!in_array($target, [CommonITILObject::INCOMING, CommonITILObject::ASSIGNED], true)) {
            return CommonITILObject::ASSIGNED;
        }
        return $target;
    }

    private static function followupContent(string $reason): string
    {
        $escaped = function_exists('htmlescape')
            ? htmlescape($reason)
            : htmlspecialchars($reason, ENT_QUOTES, 'UTF-8');

        return '<p><strong>Reabertura solicitada</strong></p><p>' . nl2br($escaped, false) . '</p>';
    }

    /**
     * O solicitante normalmente não tem direito de alterar status nem o ciclo de vida
     * Fechado -> Em atendimento. A elevação vale só durante esta ação.
     */
    private static function withReopenRights(int $fromStatus, int $toStatus, callable $callback): void
    {
        $profile = &$_SESSION['glpiactiveprofile'];

        $backupTicket = $profile['ticket'] ?? 0;
        $backupFollowup = $profile['followup'] ?? 0;
        $hadMatrix = array_key_exists('ticket_status', $profile);
        $matrixBackup = $hadMatrix ? $profile['ticket_status'] : null;

        $profile['ticket'] = $backupTicket | READ | UPDATE;
        $profile['followup'] = $backupFollowup | ITILFollowup::ADDMY | ITILFollowup::ADDALLITEM;

        if (!isset($profile['ticket_status']) || !is_array($profile['ticket_status'])) {
            $profile['ticket_status'] = [];
        }
        if (!isset($profile['ticket_status'][$fromStatus]) || !is_array($profile['ticket_status'][$fromStatus])) {
            $profile['ticket_status'][$fromStatus] = [];
        }
        $profile['ticket_status'][$fromStatus][$toStatus] = 1;

        try {
            $callback();
        } finally {
            $profile['ticket'] = $backupTicket;
            $profile['followup'] = $backupFollowup;
            if ($hadMatrix) {
                $profile['ticket_status'] = $matrixBackup;
            } else {
                unset($profile['ticket_status']);
            }
        }
    }
}
