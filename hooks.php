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

class hooks_ksf_FA_RBAC extends hooks {
    var $module_name = 'ksf_FA_RBAC';
    var $version = '1.0.0';

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
     * Lazy-provision a user into the person registry and RBAC system.
     *
     * Called from the FA `authenticate` hook (after successful login).
     * Creates or updates the crm_persons/crm_contacts rows and initializes
     * the {userId}_individual team if not already provisioned.
     *
     * @param array $data User data passed by hook_invoke_all
     * @param array $opts Reserved
     * @return void
     *
     * @since 1.0.0
     */
    function authenticate(&$data, $opts = array()) {
        global $db;

        // Extract user details from the session or data array.
        $user = isset($data['user']) ? $data['user'] : null;
        if (!$user || !isset($user->user_id, $user->login, $user->name, $user->email)) {
            return;
        }

        // Provision the user.
        try {
            $this->_ensureComposerDependencies();

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
            // Log but do not block login on provisioning failure.
            error_log('RBAC user provisioning failed: ' . $e->getMessage());
        }
    }

    /**
     * Ensure Composer dependencies are installed.
     *
     * @return void
     *
     * @since 1.0.0
     */
    private function _ensureComposerDependencies() {
        $module_dir = dirname(__FILE__);
        $autoload_path = $module_dir . '/vendor/autoload.php';

        if (file_exists($autoload_path)) {
            require_once $autoload_path;
            return;
        }

        $composer_path = $module_dir . '/composer.json';
        if (!file_exists($composer_path)) {
            return;
        }

        $composer_lock = $module_dir . '/composer.lock';
        if (!file_exists($composer_lock)) {
            return;
        }

        chdir($module_dir);
        $output = array();
        $return_code = 0;
        exec('composer install --no-interaction --prefer-dist 2>&1', $output, $return_code);
        if ($return_code !== 0) {
            error_log('KSF RBAC: composer install failed: ' . implode("\n", $output));
        }

        if (file_exists($autoload_path)) {
            require_once $autoload_path;
        }
    }
}
