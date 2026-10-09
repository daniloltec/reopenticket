<?php

/**
 * Hooks do plugin Reopen Ticket.
 */

use Glpi\Application\View\TemplateRenderer;

/**
 * Mostra o botão de reabertura no topo da aba principal do chamado.
 */
function plugin_reopenticket_pre_show_item($params = [])
{
    if (!isset($params['item']) || !($params['item'] instanceof Ticket)) {
        return;
    }

    $ticket = $params['item'];
    if (!PluginReopenticketTicketreopen::canReopen($ticket)) {
        return;
    }

    global $CFG_GLPI;

    $status = (int) $ticket->fields['status'];
    $statusLabel = $status === CommonITILObject::CLOSED ? 'fechado' : 'solucionado';

    TemplateRenderer::getInstance()->display('@reopenticket/reopen_modal.html.twig', [
        'tickets_id'     => (int) $ticket->getID(),
        'status_label'   => $statusLabel,
        'require_reason' => PluginReopenticketConfig::enabled('require_reason', true),
        'form_url'       => $CFG_GLPI['root_doc'] . '/plugins/reopenticket/front/reopen.php',
        'csrf_token'     => Session::getNewCSRFToken(),
    ]);
}
