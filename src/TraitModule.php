<?php declare(strict_types=1);
/*
 * Copyright Daniel Berthereau, 2017-2026
 * Copyright Volodimir Shumeyko, 2026
 *
 * This software is governed by the CeCILL license by the rules of distribution
 * of free software.  You can use, modify and/ or redistribute the software
 * under the terms of the CeCILL license as circulated by CEA, CNRS and INRIA
 * at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace VocabularyAddon;

use Omeka\Stdlib\PsrMessage;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Module\Exception\ModuleCannotInstallException;
use Omeka\Module\Manager as ModuleManager;

trait TraitModule
{

    /**
     * Get the config of the current module.
     *
     * @return array
     */
    public function getConfig()
    {
        return include $this->modulePath() . '/config/module.config.php';
    }

    /**
     * Check if a file exists, is readable, and is not empty.
     */
    protected function isFileReadable(string $filepath): bool
    {
        return file_exists($filepath) && filesize($filepath) && is_readable($filepath);
    }

    public function install(ServiceLocatorInterface $services): void
    {
        // This method allows to use this trait like a parent.
        $this->installAuto($services);
    }

    protected function installAuto(ServiceLocatorInterface $services): void
    {
        $this->setServiceLocator($services);

        $this->initTranslations();

        /**@var \Laminas\Mvc\I18n\Translator $translator */
        $translator = $services->get(TranslatorInterface::class);

        $this->preInstall();
        if (!$this->checkDependencies()) {
            if (count($this->dependencies) === 1) {
                $message = new PsrMessage(
                    'This module requires the module "{module}".', // @translate
                    ['module' => reset($this->dependencies)]
                );
            } else {
                $message = new PsrMessage(
                    'This module requires modules "{modules}".', // @translate
                    ['modules' => implode('", "', $this->dependencies)]
                );
            }
            throw new ModuleCannotInstallException((string) $message->setTranslator($translator));
        }

        $sqlFile = $this->modulePath() . '/data/install/schema.sql';
        if (!$this->checkNewTablesFromFile($sqlFile)) {
            $message = new PsrMessage(
                'This module cannot install its tables, because they exist already. Try to remove them first.' // @translate
            );
            throw new ModuleCannotInstallException((string) $message->setTranslator($translator));
        }

        $this->execSqlFromFile($sqlFile);

    }

    public function uninstall(ServiceLocatorInterface $services): void
    {
        $this->setServiceLocator($services);
        $this->preUninstall();
        $this->execSqlFromFile($this->modulePath() . '/data/install/uninstall.sql');
        $this->postUninstall();
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $services): void
    {
        $this->setServiceLocator($services);

        $this->preUpgrade($oldVersion, $newVersion);
        $this->postUpgrade($oldVersion, $newVersion);

        // To clear cache after upgrade avoids some mysterious issues, in
        // particular when a doctrine entity is modified.
        // But invalidate only current module files instead of resetting entire
        // opcache to avoid jit segfaults on multiple upgrades with apache.
        $this->clearCaches($this->modulePath());
    }

    protected function preInstall(): void
    {
        // To be overridden. Automatically run on install.
    }

    protected function postInstall(): void
    {
        // To be overridden. Automatically run on install.
        $this->postInstallAuto();
    }

    protected function postInstallAuto(): void
    {
        $filepath = $this->modulePath() . '/data/scripts/install.php';
        if ($this->isFileReadable($filepath)) {
            // Required for the file install.
            /** @var \Laminas\ServiceManager\ServiceLocatorInterface $services */
            $services = $this->getServiceLocator();
            require_once $filepath;
        }
    }

    protected function preUninstall(): void
    {
        // To be overridden. Automatically run on uninstall.
    }

    protected function postUninstall(): void
    {
        // To be overridden. Automatically run on uninstall.
        $this->postUninstallAuto();
    }

    protected function postUninstallAuto(): void
    {
        $filepath = $this->modulePath() . '/data/scripts/uninstall.php';
        if ($this->isFileReadable($filepath)) {
            // Required for the file uninstall.
            /** @var \Laminas\ServiceManager\ServiceLocatorInterface $services */
            $services = $this->getServiceLocator();
            require_once $filepath;
        }
    }

    protected function preUpgrade(?string $oldVersion, ?string $newVersion): void
    {
        // To be overridden. Automatically run on upgrade.
    }

    protected function postUpgrade(?string $oldVersion, ?string $newVersion): void
    {
        // To be overridden. Automatically run on upgrade.
        $this->postUpgradeAuto($oldVersion, $newVersion);
    }

    protected function postUpgradeAuto(?string $oldVersion, ?string $newVersion): void
    {
        $filepath = $this->modulePath() . '/data/scripts/upgrade.php';
        if ($this->isFileReadable($filepath)) {
            // Required for the file upgrade.
            /** @var \Laminas\ServiceManager\ServiceLocatorInterface $services */
            $services = $this->getServiceLocator();
            // For compatibility with old upgrade files.
            $this->initTranslations();
            require_once $filepath;
        }
    }

    /**
     * Init translations during install and upgrade, when the config is not included early.
     *
     * @fixme The translation are currently not included here (earlier event and factory)
     */
    protected function initTranslations(): self
    {
        // Include translations early for translatable settings and messages.
        $conf = $this->getConfig();
        if (!isset($conf['translator']['translation_file_patterns'])
            || !is_array($conf['translator']['translation_file_patterns'])
        ) {
            return $this;
        }

        $services = $this->getServiceLocator();

        /**
         * @var \Laminas\I18n\Translator\TranslatorInterface $translator
         * @var \Laminas\I18n\Translator\Translator $delegatedTranslator
         */
        $translator = $services->get(TranslatorInterface::class);
        $delegatedTranslator = $translator->getDelegatedTranslator();
        foreach ($conf['translator']['translation_file_patterns'] as $translationFilePattern) {
            $delegatedTranslator->addTranslationFilePattern(
                $translationFilePattern['type'],
                $translationFilePattern['base_dir'],
                $translationFilePattern['pattern'],
                $translationFilePattern['text_domain'] ?? 'default',
            );
        }

        return $this;
    }

    /**
     * Check if new tables can be installed and remove empty existing tables.
     *
     * If a new table exists and is empty, it is removed, because it is probably
     * related to a broken installation.
     */
    protected function checkNewTablesFromFile(string $filepath): bool
    {
        if (!$this->isFileReadable($filepath)) {
            return true;
        }

        // Get the list of all tables.
        $tables = $this->getConnection()->executeQuery('SHOW TABLES;')->fetchFirstColumn();

        $dropTables = [];

        // Use single statements for execution.
        // See core commit #2689ce92f.
        $sql = file_get_contents($filepath);
        $sqls = array_filter(array_map('trim', explode(";\n", $sql)));
        foreach ($sqls as $sql) {
            if (mb_strtoupper(mb_substr($sql, 0, 13)) !== 'CREATE TABLE ') {
                continue;
            }
            $table = trim(strtok(mb_substr($sql, 13), '('), "\"`' \n\r\t\v\0");
            if (!in_array($table, $tables)) {
                continue;
            }
            $result = $this->getConnection()->executeQuery("SELECT * FROM `$table` LIMIT 1;")->fetchOne();
            if ($result !== false) {
                return false;
            }
            $dropTables[] = $table;
        }

        if (count($dropTables)) {
            // No check: if a table cannot be removed, an exception will be
            // thrown later.
            foreach ($dropTables as $table) {
                $this->getConnection()->executeStatement("SET FOREIGN_KEY_CHECKS=0; DROP TABLE `$table`;");
            }

            $message = new PsrMessage(
                'The module removed tables "{tables}" from a previous broken install.', // @translate
                ['tables' => implode('", "', $dropTables)]
            );
            $messenger = $this->getControllerPluginManager()->get('messenger');
            $messenger->addWarning($message);
        }

        return true;
    }

    /**
     * Execute a sql from a file.
     *
     * @param string $filepath
     * @return int|null
     */
    protected function execSqlFromFile(string $filepath): ?int
    {
        if (!$this->isFileReadable($filepath)) {
            return null;
        }

        // Use single statements for execution.
        // See core commit #2689ce92f.
        $sql = file_get_contents($filepath);
        $sqls = array_filter(array_map('trim', explode(";\n", $sql)));
        $result = null;
        foreach ($sqls as $sql) {
            $result = $this->getConnection()->executeStatement($sql);
        }

        return $result;
    }

    /**
     * Check if the current process is a background one.
     *
     * The library to get status manages only admin, site or api requests.
     * A background process is none of them.
     */
    protected function isBackgroundProcess(): bool
    {
        // Warning: there is a matched route ("site") for backend processes.
        /** @var \Omeka\Mvc\Status $status */
        $status = $this->getServiceLocator()->get('Omeka\Status');
        return !$status->isSiteRequest()
            && !$status->isAdminRequest()
            && !$status->isApiRequest()
            && (!method_exists($status, 'isKeyauthRequest') || !$status->isKeyauthRequest());
    }

    /**
     * Check if the module has dependencies.
     *
     * @return bool
     */
    protected function checkDependencies(): bool
    {
        return empty($this->dependencies)
            || $this->areModulesActive($this->dependencies);
    }

    /**
     * Check the version of a module and return a boolean or throw an exception.
     *
     * @throws \Omeka\Module\Exception\ModuleCannotInstallException
     */
    protected function checkModuleAvailability(string $moduleName, ?string $version = null, bool $required = false, bool $exception = false): bool
    {
        $services = $this->getServiceLocator();
        $module = $services->get('Omeka\ModuleManager')->getModule($moduleName);
        if (!$module || !$this->isModuleActive($moduleName)) {
            if (!$required) {
                return true;
            }
            if (!$exception) {
                return false;
            }
            // Else throw message below (required module with a version or not).
        } elseif (!$version || version_compare($module->getIni('version') ?? '', $version, '>=')) {
            return true;
        } elseif (!$exception) {
            return false;
        }
        $translator = $services->get(TranslatorInterface::class);
        if ($version) {
            $message = new PsrMessage(
                'This module requires the module "{module}", version {version} or above.', // @translate
                ['module' => $moduleName, 'version' => $version]
            );
        } else {
            $message = new PsrMessage(
                'This module requires the module "{module}".', // @translate
                ['module' => $moduleName]
            );
        }
        throw new ModuleCannotInstallException((string) $message->setTranslator($translator));
    }

    /**
     * Check if a module is active and optionally its minimum version.
     */
    protected function checkModuleActiveVersion(string $module, ?string $version = null): bool
    {
        $services = $this->getServiceLocator();
        /** @var \Omeka\Module\Manager $moduleManager */
        $moduleManager = $services->get('Omeka\ModuleManager');
        $module = $moduleManager->getModule($module);
        if (!$module
            || $module->getState() !== ModuleManager::STATE_ACTIVE
        ) {
            return false;
        }

        if (!$version) {
            return true;
        }

        $moduleVersion = $module->getIni('version');
        return $moduleVersion
            && version_compare($moduleVersion, $version, '>=');
    }

    /**
     * Check the version of a module.
     */
    protected function isModuleVersionAtLeast(string $module, string $version): bool
    {
        $services = $this->getServiceLocator();
        /** @var \Omeka\Module\Manager $moduleManager */
        $moduleManager = $services->get('Omeka\ModuleManager');
        $module = $moduleManager->getModule($module);
        if (!$module) {
            return false;
        }

        $moduleVersion = $module->getIni('version');
        return $moduleVersion
            && version_compare($moduleVersion, $version, '>=');
    }

    /**
     * Check if a module is active.
     *
     * @param string $module
     * @return bool
     */
    protected function isModuleActive(string $module): bool
    {
        return $this->areModulesActive([$module]);
    }

    /**
     * Check if a list of modules are active.
     *
     * @param array $modules
     * @return bool
     */
    protected function areModulesActive(array $modules): bool
    {
        $services = $this->getServiceLocator();
        /** @var \Omeka\Module\Manager $moduleManager */
        $moduleManager = $services->get('Omeka\ModuleManager');
        foreach ($modules as $moduleName) {
            $module = $moduleManager->getModule($moduleName);
            if (!$module || $module->getState() !== ModuleManager::STATE_ACTIVE) {
                return false;
            }
        }
        return true;
    }

    /**
     * Disable a module.
     *
     * @param string $module
     * @return bool
     */
    protected function disableModule(string $module): bool
    {
        // Check if the module is enabled first to avoid an exception.
        if (!$this->isModuleActive($module)) {
            return true;
        }

        // Check if the user is a global admin to avoid right issues.
        // $services = $this->getServiceLocator();
        // $user = $this->getServiceLocator()->get('Omeka\AuthenticationService')->getIdentity();
        
        // if (!$user || $user->getRole() !== \Omeka\Permissions\Acl::ROLE_GLOBAL_ADMIN) {
        if ($this->getRoleCurrentUser() !== \Omeka\Permissions\Acl::ROLE_GLOBAL_ADMIN) {
            return false;
        }

        /** @var \Omeka\Module\Manager $moduleManager */
        $managedModule = $this->getModuleManager()->getModule($module);
        $this->getModuleManager()->deactivate($managedModule);

        $this->ensurePsrMessage();
        $message = new PsrMessage(
            'The module "{module}" was automatically deactivated because the dependencies are unavailable.', // @translate
            ['module' => $module]
        );
        $this->getControllerPluginManager()->addWarning($message);
        $this->getLogger()->warn($message->getMessage(), $message->getContext());
        return true;
    }

    /**
     * Check or create the destination folder.
     *
     * @param string $dirPath Absolute path of the directory to check.
     * @return string|null The dirpath if valid, else null.
     */
    protected function checkDestinationDir(string $dirPath): ?string
    {
        if (file_exists($dirPath)) {
            if (!is_dir($dirPath) || !is_readable($dirPath) || !is_writeable($dirPath)) {
                $this->getServiceLocator()->get('Omeka\Logger')->err(
                    'The directory "{path}" is not writeable.', // @translate
                    ['path' => $dirPath]
                );
                return null;
            }
            return $dirPath;
        }

        $result = @mkdir($dirPath, 0775, true);
        if (!$result) {
            $this->getServiceLocator()->get('Omeka\Logger')->err(
                'The directory "{path}" is not writeable: {error}.', // @translate
                ['path' => $dirPath, 'error' => error_get_last()['message'] ?? 'unknown error']
            );
            return null;
        }
        return $dirPath;
    }

    /**
     * Remove a dir from filesystem.
     *
     * @param string $dirPath Absolute path.
     * @return bool
     */
    protected function rmDir(string $dirPath): bool
    {
        if (!file_exists($dirPath)) {
            return true;
        }
        if (strpos($dirPath, '/..') !== false || substr($dirPath, 0, 1) !== '/') {
            return false;
        }
        $files = array_diff(scandir($dirPath) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dirPath . '/' . $file;
            if (is_dir($path)) {
                $this->rmDir($path);
            } else {
                unlink($path);
            }
        }
        return rmdir($dirPath);
    }

    /**
     * Clear php and doctrine caches.
     *
     * This method may be used when updating the schema of an entity, in which
     * case the cache may need to be refreshed.
     *
     * When a module path is provided, only its php and phtml files are
     * invalidated to avoid a race condition in apache when multiple modules are
     * upgraded quickly and the general cache has no time to be rebuilt.
     *
     * @see https://github.com/php/php-src/issues/20818
     * @see https://github.com/php/php-src/issues/13508
     */
    private function clearCaches(?string $modulePath = null): void
    {
        // Invalidate OPcache: targeted per-module or full reset as fallback.
        if ($modulePath && function_exists('opcache_invalidate')) {
            $this->opcacheInvalidateDirectory($modulePath);
        } elseif (function_exists('opcache_reset')) {
            opcache_reset();
        }

        // Clear Doctrine metadata cache to fix issue with entity schema change.
        try {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $config = $this->getEntityManager()->getConfiguration();
            // ORM 2.14+/3.x uses getMetadataCache() (PSR-6). ORM 2.7–2.13 uses
            // getMetadataCacheImpl() (Doctrine Cache).
            $cache = method_exists($config, 'getMetadataCache')
                ? $config->getMetadataCache()
                : $config->getMetadataCacheImpl();
            if ($cache) {
                if (method_exists($cache, 'clear')) {
                    $cache->clear();
                } elseif (method_exists($cache, 'deleteAll')) {
                    $cache->deleteAll();
                }
            }
        } catch (\Throwable $e) {
            // Ignore.
        }

        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }

        @clearstatcache(true);
    }

    /**
     * Invalidate OPcache entries for all PHP files in a directory.
     */
    private function opcacheInvalidateDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (in_array($file->getExtension(), ['php', 'phtml'], true)) {
                opcache_invalidate($file->getPathname(), true);
            }
        }
    }

}
