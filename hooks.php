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
            $updates = array(
                'install.sql'           => array($this->module_name),
                'retag_contact_types.sql' => array('ksf_contact_types'),
            );
            $ok = $this->update_databases($company, $updates, $check_only);
        } else {
            $ok = true;
        }

        if (!$check_only && $ok) {
            $this->register_contact_types();
        }

        return $ok;
    }

    /**
     * Register the contact types owned by this module (idempotent).
     *
     * @since 1.1.0
     */
    private function register_contact_types() {
        $autoload = dirname(__FILE__) . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }
        if (!class_exists('\\ksfraser\\FrontAccounting\\Common\\ContactType\\ContactTypeRegistry')) {
            return;
        }

        \ksfraser\FrontAccounting\Common\ContactType\ContactTypeRegistry::registerTypes(array(
            new \ksfraser\FrontAccounting\Common\ContactType\ContactType(
                'fa_user', 'FA User', $this->module_name,
                'FrontAccounting RBAC user account'
            ),
        ));
    }

    function deactivate_extension($company, $check_only = true) {
        if (!$check_only
            && class_exists('\\ksfraser\\FrontAccounting\\Common\\ContactType\\ContactTypeRegistry')) {
            \ksfraser\FrontAccounting\Common\ContactType\ContactTypeRegistry::unregisterModule($this->module_name);
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
            }

            $dbAdapter   = new \ksfraser\CommonDb\Adapter\FaDbAdapter(TB_PREF);
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
     * Uses RbacService with voter-based authorization.
     * Falls back to legacy FaTeamRepository for backward compatibility.
     *
     * @param array &$data {
     *     @var int    $user_id       FA user ID
     *     @var string $action        'create' | 'view' | 'edit' | 'delete' | 'list' | 'export'
     *     @var string $module        Module name (e.g. 'customer', 'payment')
     *     @var string $resource_type Resource type (e.g. 'customer', 'payment')
     *     @var int    $resource_id   Optional record ID for view/edit/delete
     *     @var mixed  $resource      Optional resource object for record-level checks
     * }
     * @param array|null $opts Reserved
     * @return bool|null True=allowed, False=denied, Null=no opinion
     *
     * @since 2.0
     */
    function authorize(&$data, $opts = null)
    {
        $userId  = isset($data['user_id']) ? (int) $data['user_id'] : 0;
        $action  = isset($data['action']) ? (string) $data['action'] : '';
        $module  = isset($data['module']) ? (string) $data['module'] : '';
        $resType = isset($data['resource_type']) ? (string) $data['resource_type'] : '';
        $resId   = isset($data['resource_id']) ? (int) $data['resource_id'] : null;
        $resource = isset($data['resource']) ? $data['resource'] : null;

        if ($userId <= 0 || $action === '') {
            return null;
        }

        try {
            $this->_ensureComposerDependencies();

            $rbacService = $this->_getRbacService();
            $token = \Ksfraser\FrontAccounting\Rbac\Token\FaUserToken::fromSession();

            if ($module !== '' && $resType !== '') {
                $result = $rbacService->authorize($action, $resource, $token, [
                    'module' => $module,
                    'resource_type' => $resType,
                    'resource_id' => $resId,
                    'user_id' => $userId,
                ]);

                if ($result !== null) {
                    return $result;
                }
            }

            if (!class_exists('Ksfraser\FrontAccounting\Rbac\Repository\FaTeamRepository')) {
                require_once dirname(__FILE__) . '/src/Ksfraser/FrontAccounting/Rbac/Repository/FaTeamRepository.php';
                require_once dirname(__FILE__) . '/src/Ksfraser/FrontAccounting/Rbac/Repository/FaRecordAccessRepository.php';
            }

            $dbAdapter = new \ksfraser\CommonDb\Adapter\FaDbAdapter(TB_PREF);
            $teamRepo  = new \Ksfraser\FrontAccounting\Rbac\Repository\FaTeamRepository($dbAdapter);

            $teamIds = $teamRepo->findEffectiveTeamIdsForUser((string) $userId);

            if (empty($teamIds)) {
                return false;
            }

            if ($action === 'create') {
                return true;
            }

            if ($resId !== null && $module !== '' && $resType !== '') {
                $accessRepo = new \Ksfraser\FrontAccounting\Rbac\Repository\FaRecordAccessRepository($dbAdapter);
                $records    = $accessRepo->findForRecord($module, $resType, $resId, $teamIds);

                $capField = 'can_' . $action;

                foreach ($records as $access) {
                    $caps = $access->getCapabilities()->toArray();
                    if (!empty($caps[$capField])) {
                        return true;
                    }
                }

                return false;
            }

            return true;
        } catch (\Exception $e) {
            error_log('KSF RBAC: authorize check failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the RbacService instance.
     *
     * @return \Ksfraser\FrontAccounting\Rbac\RbacService
     *
     * @since 2.0
     */
    private function _getRbacService()
    {
        static $service = null;

        if ($service === null) {
            $service = new \Ksfraser\FrontAccounting\Rbac\RbacService();
        }

        return $service;
    }

    // =======================================================================
    // FILTER RECORD LIST HOOK — called by list queries
    //
    // Modules call:
    //   $data = ['user_id' => 5, 'module' => 'customer', 'action' => 'list',
    //            'sql' => "SELECT * FROM debtor_master WHERE 1=1"];
    //   hook_invoke_all('filterRecordList', $data);
    //   $sql = $data['sql'];
    //
    // Returns modified SQL with WHERE clauses applied
    // =======================================================================

    /**
     * Filter a record list based on user access.
     *
     * @param array &$data {
     *     @var int    $user_id
     *     @var string $module
     *     @var string $action
     *     @var string $sql
     *     @var array  $params
     * }
     * @param array|null $opts
     * @return array|null
     *
     * @since 2.0
     */
    function filterRecordList(&$data, $opts = null)
    {
        $userId  = isset($data['user_id']) ? (int) $data['user_id'] : 0;
        $module  = isset($data['module']) ? (string) $data['module'] : '';
        $action  = isset($data['action']) ? (string) $data['action'] : 'list';

        if ($userId <= 0 || $module === '') {
            return null;
        }

        try {
            $this->_ensureComposerDependencies();

            $rbacService = $this->_getRbacService();
            $token = \Ksfraser\FrontAccounting\Rbac\Token\FaUserToken::fromSession();

            $acl = $rbacService->getModuleAcl($module);

            if (empty($acl)) {
                return null;
            }

            if (!isset($acl[$action])) {
                $action = 'list';
            }

            if (!isset($acl[$action])) {
                return null;
            }

            $allowedRoles = $acl[$action];

            if (!$token->hasAnyRole($allowedRoles)) {
                if ($token->hasRole('salesman')) {
                    $data['sql'] = $this->_addSalesmanFilter($data['sql'], $module);
                } else {
                    $data['sql'] .= " AND 1=0";
                }
            }

            return null;
        } catch (\Exception $e) {
            error_log('KSF RBAC: filterRecordList failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Add salesman-based filter to SQL.
     *
     * @param string $sql
     * @param string $module
     * @return string
     *
     * @since 2.0
     */
    private function _addSalesmanFilter(string $sql, string $module): string
    {
        $salesman = $_SESSION['wa_current_user']->salesman ?? '';

        if (empty($salesman)) {
            return $sql . " AND 1=0";
        }

        switch ($module) {
            case 'customer':
            case 'debtor_trans':
                if (strpos($sql, 'cust_branch') !== false) {
                    return $sql . " AND cust_branch.salesman = " . db_escape($salesman);
                }
                return $sql . " AND EXISTS (SELECT 1 FROM " . TB_PREF . "cust_branch cb WHERE cb.debtor_no = debtor_master.debtor_no AND cb.salesman = " . db_escape($salesman) . ")";

            default:
                return $sql;
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
