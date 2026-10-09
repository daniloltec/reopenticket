<?php

include('../../../inc/includes.php');

Session::checkLoginUser();

global $CFG_GLPI;

$tickets_id = (int) ($_POST['tickets_id'] ?? 0);
$fallback = $CFG_GLPI['root_doc'] . '/front/ticket.php';
$url = $tickets_id > 0 ? Ticket::getFormURLWithID($tickets_id) : $fallback;

if (!isset($_POST['reopen'])) {
    Html::redirect($url);
}

$result = PluginReopenticketTicketreopen::reopen($tickets_id, (string) ($_POST['reason'] ?? ''));

Session::addMessageAfterRedirect(
    $result['message'],
    $result['ok'],
    $result['ok'] ? INFO : ERROR
);

Html::redirect($url);
