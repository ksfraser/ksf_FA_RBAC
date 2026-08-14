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
    \ksfraser\FrontAccounting\Common\Utils\ComposerDependencies::ensure(__DIR__);
}

class hooks_ksf_FA_RBAC extends hooks {
    use \Ksfraser\Traits\HookQueryProviderTrait;

    var $module_name = 'ksf_FA_RBAC';
    var $version = '1.0.0';

    /**
     * Constructor.
     *
     * Provisioning is deferred to pre_header(): the hooks class is
     * instantiated during session bootstrap before the company DB
     * connection and TB_PREF are available.
     *
     * @since 1.0.0
     */
    function __construct() {
    }

    /**
     * Lazy-provision the current user once the session and DB are ready.
     *
     * Invoked by FA before the page header is rendered on every request
     * after login.
     *
     * @param array $fun_args Reserved
     *
     * @since 1.0.0
     */
    function pre_header($fun_args = null) {
        $this->provisionCurrentUser();
    }

    /**
     * Ensure Composer dependencies are installed/autoloadable.
     *
     * Autoloads the module vendor autoloader and runs the shared
     * ComposerDependencies::ensure() helper (from ksf_FA_Common) when
     * present. Idempotent.
     *
     * @return void
     *
     * @since 1.0.0
     */
    private function _ensureComposerDependencies(): void {
        $autoload = dirname(__FILE__) . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }
        $composerDepsPath = dirname(__DIR__) . '/ksf_FA_Common/src/Utils/ComposerDependencies.php';
        if (file_exists($composerDepsPath)) {
            require_once $composerDepsPath;
            \ksfraser\FrontAccounting\Common\Utils\ComposerDependencies::ensure(dirname(__FILE__));
        }
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
        global $db;

        if (!is_object($db)) {
            return;
        }

        if (!isset($_SESSION['wa_current_user']->user)) {
            return;
        }

        $user = $_SESSION['wa_current_user'];
        if (!isset($user->user, $user->loginname, $user->name, $user->email)) {
            return;
        }

        try {
            if (!class_exists('Ksfraser\FrontAccounting\Rbac\Provisioner\UserProvisioner')) {
                require_once dirname(__FILE__) . '/src/Ksfraser/FrontAccounting/Rbac/Provisioner/UserProvisioner.php';
                require_once dirname(__FILE__) . '/src/Ksfraser/FrontAccounting/Rbac/Adapter/FaDbAdapter.php';
                require_once dirname(__FILE__) . '/src/Ksfraser/FrontAccounting/Rbac/Contract/DbAdapterInterface.php';
            }

            $dbAdapter   = new \Ksfraser\FrontAccounting\Rbac\Adapter\FaDbAdapter(TB_PREF);
            $provisioner = new \Ksfraser\FrontAccounting\Rbac\Provisioner\UserProvisioner($dbAdapter);

            $provisioner->provision(
                (int) $user->user,
                (string) $user->loginname,
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

    // =======================================================================
    // AUTHORIZATION HOOK — called by other modules via hook_invoke_first()
    //
    // Other modules call:
    //   $data = ['user_id' => 5, 'action' => 'create', 'module' => 'customer',
    //            'resource_type' => 'customer', 'resource_id' => null];
    //   $allowed = hook_invoke_first('authorize', $data);
    //   if ($allowed === false) { /* deny */ }
    //
    // Returns:
    //   true  — allowed
    //   false — denied
    //   null  — no opinion (module not fully loaded, or check not applicable)
    // =======================================================================

    /**
     * Authorize a user action against the RBAC system.
     *
     * Checks whether the given user has the required capability for a resource.
     * For create actions (no resource_id), membership in at least one team
     * is sufficient. For view/edit/delete actions, the specific record access
     * is checked via FaRecordAccessRepository::findForRecord().
     *
     * @param array &$data {
     *     @var int    $user_id       FA user ID
     *     @var string $action        'create' | 'view' | 'edit' | 'delete'
     *     @var string $module        Module name (e.g. 'customer', 'payment')
     *     @var string $resource_type Resource type (e.g. 'customer', 'payment')
     *     @var int    $resource_id   Optional record ID for view/edit/delete
     * }
     * @param array|null $opts Reserved
     * @return bool|null True=allowed, False=denied, Null=no opinion
     *
     * @since 1.1.0
     */
    function authorize(&$data, $opts = null)
    {
        $userId      = isset($data['user_id']) ? (int) $data['user_id'] : 0;
        $action      = isset($data['action']) ? (string) $data['action'] : '';
        $module      = isset($data['module']) ? (string) $data['module'] : '';
        $resType     = isset($data['resource_type']) ? (string) $data['resource_type'] : '';
        $resId       = isset($data['resource_id']) ? (int) $data['resource_id'] : null;

        if ($userId <= 0 || $action === '') {
            return null;
        }

        try {
            $this->_ensureComposerDependencies();

            if (!class_exists('Ksfraser\FrontAccounting\Rbac\Adapter\FaDbAdapter')) {
                require_once dirname(__FILE__) . '/src/Ksfraser/FrontAccounting/Rbac/Contract/DbAdapterInterface.php';
                require_once dirname(__FILE__) . '/src/Ksfraser/FrontAccounting/Rbac/Adapter/FaDbAdapter.php';
                require_once dirname(__FILE__) . '/src/Ksfraser/FrontAccounting/Rbac/Repository/FaTeamRepository.php';
                require_once dirname(__FILE__) . '/src/Ksfraser/FrontAccounting/Rbac/Repository/FaRecordAccessRepository.php';
            }

            $dbAdapter = new \Ksfraser\FrontAccounting\Rbac\Adapter\FaDbAdapter(TB_PREF);
            $teamRepo  = new \Ksfraser\FrontAccounting\Rbac\Repository\FaTeamRepository($dbAdapter);

            $teamIds = $teamRepo->findEffectiveTeamIdsForUser((string) $userId);

            if (empty($teamIds)) {
                return false;
            }

            // Create actions — user belongs to at least one team
            if ($action === 'create') {
                return true;
            }

            // Record-level actions (view, edit, delete) — check specific record
            if ($resId !== null && $module !== '' && $resType !== '') {
                $accessRepo = new \Ksfraser\FrontAccounting\Rbac\Repository\FaRecordAccessRepository($dbAdapter);
                $records    = $accessRepo->findForRecord($module, $resType, $resId, $teamIds);

                $capField = 'can_' . $action; // e.g. 'can_view', 'can_edit', 'can_delete'

                foreach ($records as $access) {
                    $caps = $access->getCapabilities()->toArray();
                    if (!empty($caps[$capField])) {
                        return true;
                    }
                }

                return false;
            }

            // Default — allow (user has teams, no specific record constraint)
            return true;
        } catch (\Exception $e) {
            error_log('KSF RBAC: authorize check failed: ' . $e->getMessage());
            return null;
        }
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
