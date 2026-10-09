<?php

/**
 * Direito do plugin na aba do perfil.
 * READ: reabrir chamados visíveis, mesmo sem ser o solicitante.
 */
class PluginReopenticketProfile extends Profile
{
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof Profile && $item->getID()) {
            return self::createTabEntry('Reabrir chamado', 0, Profile::class, 'ti ti-refresh');
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof Profile) {
            $profile = new self();
            $profile->showForm($item->getID());
        }
        return true;
    }

    public function showForm($ID, array $options = [])
    {
        $profile = new Profile();
        if (!$profile->getFromDB($ID)) {
            return false;
        }

        $canedit = Session::haveRight(self::$rightname, UPDATE);
        if ($canedit) {
            echo "<form method='post' action='" . htmlescape($profile->getFormURL()) . "'>";
        }

        $profile->displayRightsChoiceMatrix([
            [
                'itemtype' => PluginReopenticketTicketreopen::class,
                'label'    => 'Reabrir chamado',
                'field'    => 'plugin_reopenticket',
                'rights'   => [
                    READ => 'Reabrir qualquer chamado visível',
                ],
            ],
        ], [
            'canedit'       => $canedit,
            'default_class' => 'tab_bg_2',
            'title'         => 'Reabrir chamado',
        ]);

        if ($canedit) {
            echo Html::hidden('id', ['value' => $ID]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
            Html::closeForm();
        }

        return true;
    }

    public static function installRights(): void
    {
        global $DB;

        $existing = [];
        $iterator = $DB->request([
            'FROM'  => 'glpi_profilerights',
            'WHERE' => ['name' => 'plugin_reopenticket'],
        ]);
        foreach ($iterator as $row) {
            $existing[(int) $row['profiles_id']] = true;
        }

        ProfileRight::addProfileRights(['plugin_reopenticket']);

        $profiles = $DB->request([
            'FROM'  => 'glpi_profiles',
            'WHERE' => ['interface' => 'central'],
        ]);
        foreach ($profiles as $profile) {
            $profileId = (int) $profile['id'];
            if (isset($existing[$profileId])) {
                continue;
            }
            $DB->update('glpi_profilerights', [
                'rights' => READ,
            ], [
                'name'        => 'plugin_reopenticket',
                'profiles_id' => $profileId,
            ]);
        }
    }

    public static function uninstallRights(): void
    {
        ProfileRight::deleteProfileRights(['plugin_reopenticket']);
    }
}
