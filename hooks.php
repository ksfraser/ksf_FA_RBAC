<?php
/**
 * KSF FrontAccounting RBAC Module Hooks
 *
 * Integrates the ksfraser/rbac library with FA's user authentication system.
 *
 * @package KsfFA_RBAC
 * @version 1.0.0
 */

define('SS_ksf_FA_RBAC', 126 << 8);

// ---------------------------------------------------------------------------
// Ensure Composer autoloader is loaded before the class definition so that
// trait dependencies (HookQueryProviderTrait) are available at class-load time.
// ---------------------------------------------------------------------------
$rbacAutoload = dirname(__FILE__) . '/vendor/autoload.php';
if (file_exists($rbacAutoload)) {
    require_once $rbacAutoload;
}

// Shared utility: ensure Composer dependencies are installed (runs once).
$composerDepsPath = dirname(__DIR__) . '/ksf_FA_Common/src/Utils/ComposerDependencies.php';
if (file_exists($composerDepsPath)) {
    require_once $composerDepsPath;
    \KsfCommon\Utils\ComposerDependencies::ensure(__DIR__);
}

class hooks_ksf_FA_RBAC extends hooks {
    use \Ksfraser\Traits\HookQueryProviderTrait;

    var $module_name = 'ksf_FA_RBAC';
    var $version = '1.0.0';

    /**
     * Constructor — performs lazy user provisioning on page access.
     *
     * The hooks class is instantiated on every request after the session is
     * established.  If the current user has not yet been provisioned into the
     * person registry and RBAC system, this creates the necessary rows.
     *
     * @since 1.0.0
     */
    function __construct() {
        $this->provisionCurrentUser();
    }

    /**
     * Activate the RBAC module: create tables and seed initial data.
     *
     * @param string $company
     * @param bool   $check_only
     * @return bool|array
     *
     * @since 1.0.0
     */
    function activate_extension($company, $check_only = true) {
        if (file_exists(dirname(__FILE__) . '/sql/install.sql')) {
            $updates = array('install.sql' => array($this->module_name));
            return $this->update_databases($company, $updates, $check_only);
        }

        return true;
    }

    /**
     * Lazy-provision the current user into the person registry and RBAC system.
     *
     * Reads from the FA session after login.  Creates or updates the
     * crm_persons/crm_contacts rows and initializes the {userId}_individual
     * team if not already provisioned.  Idempotent — re-running is a no-op.
     *
     * @return void
     *
     * @since 1.0.0
     */
    private function provisionCurrentUser() {
        if (!isset($_SESSION['wa_current_user']->user)) {
            return;
        }

        $user = $_SESSION['wa_current_user'];
        if (!isset($user->user_id, $user->login, $user->name, $user->email)) {
            return;
        }

        try {
            if (!class_exists('Ksfraser\FA\Rbac\Provisioner\UserProvisioner')) {
                require_once dirname(__FILE__) . '/src/Ksfraser/FA/Rbac/Provisioner/UserProvisioner.php';
                require_once dirname(__FILE__) . '/src/Ksfraser/FA/Rbac/Adapter/FaDbAdapter.php';
                require_once dirname(__FILE__) . '/src/Ksfraser/FA/Rbac/Contract/DbAdapterInterface.php';
            }

            $dbAdapter   = new \Ksfraser\FA\Rbac\Adapter\FaDbAdapter(TB_PREF);
            $provisioner = new \Ksfraser\FA\Rbac\Provisioner\UserProvisioner($dbAdapter);

            $provisioner->provision(
                (int) $user->user_id,
                (string) $user->login,
                (string) $user->name,
                (string) $user->email
            );
        } catch (\Exception $e) {
            error_log('RBAC user provisioning failed: ' . $e->getMessage());
        }
    }

    // =======================================================================
    // KSF Query Hook System — Advertised values
    //
    // Modules call hook_invoke_first('ksf_get_value', $key) to read
    // RBAC configuration without a direct dependency on this module.
    // NOTE: Always pass a variable — FA declares &$data (by reference).
    //
    // ksf_get_value(), ksf_get_values(), ksf_set_value() are provided by
    // HookQueryProviderTrait (Ksfraser\Traits).
    // =======================================================================

    /**
     * Return all values this module advertises via the query hook system.
     *
     * @return array<string, mixed>
     *
     * @since 1.0.0
     */
    protected function _getAdvertisedValues(): array
    {
        return array(
            // Metadata
            'rbac.hooks_version'            => '2.0',
            'rbac.module_version'           => $this->version,

            // Provisioning status (lazy — only defined once the auth hook fires)
            'rbac.person_registry_active'   => defined('TB_PREF')
                ? $this->_checkPersonRegistryTable()
                : false,

            // ContactTypeRegistry info for calendar viewable_by filter
            'rbac.contact_type_registered'  => array(
                'fa_user'     => 'user',
                'crm_contact' => 'crm_contact',
            ),

            // Supported RBAC features (for capability negotiation)
            'rbac.features'                 => array(
                'projection_ranking'    => true,
                'per_type_elevation'    => true,
                'double_gated_restore'  => true,
                'team_approver_list'    => true,
                'recursive_insert'      => true,
            ),
        );
    }

    /**
     * Quick check whether the person registry tables have been provisioned.
     *
     * @return bool
     *
     * @since 1.0.0
     */
    private function _checkPersonRegistryTable()
    {
        global $db;

        if (!$db) {
            return false;
        }

        $table = TB_PREF . 'crm_categories';
        $result = db_query("SHOW TABLES LIKE '" . $table . "'", __FUNCTION__);
        return db_num_rows($result) > 0;
    }

}
