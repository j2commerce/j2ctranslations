<?php
/**
 * @copyright Copyright (C) 2025 J2Commerce, LLC. All rights reserved.
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPLv3 or later
 * @website https://www.j2commerce.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Filesystem\Exception\FilesystemException;
use Joomla\Filesystem\File;

return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(
            InstallerScriptInterface::class,
            new class (
                $container->get(AdministratorApplication::class),
                $container->get(DatabaseInterface::class)
            ) implements InstallerScriptInterface {
                private AdministratorApplication $app;
                private DatabaseInterface $db;

                /**
                 * A list of files to be deleted
                 *
                 * @var    array
                 */
                protected $deleteFiles = [];

                /**
                 * Minimum J2Commerce version required to install the extension
                 */
                protected $minimumJ2Commerce = '4.0.5';

                /**
                 * J2Commerce link for download
                 */
                protected $downloadLink = 'https://j2commerce.com/download';

                protected $languagePack = 'tr-TR';

                public function __construct(AdministratorApplication $app, DatabaseInterface $db)
                {
                    $this->app = $app;
                    $this->db  = $db;
                }

                public function preflight(string $type, InstallerAdapter $parent): bool
                {
                    // Check if J2Commerce is installed and the language pack is compatible with it.
                    if (is_dir(JPATH_ROOT . '/administrator/components/com_j2store')) {
                        $j2commerce_version = strval(simplexml_load_file(JPATH_SITE . '/administrator/components/com_j2store/j2store.xml')->version);
                        if (!version_compare($j2commerce_version, $this->minimumJ2Commerce, 'ge')) {
                            $message = 'This language pack is compatible with J2Commerce v' . $this->minimumJ2Commerce . ' and over.<br /><a href="' . $this->downloadLink . '" target="_blank">Download J2Commerce</a>.';
                            $this->app->enqueueMessage($message, 'error');
                            return false;
                        }
                    }

                    return true;
                }

                public function install(InstallerAdapter $parent): bool
                {
                    //$this->app->enqueueMessage('Successful installed.');

                    return true;
                }

                public function update(InstallerAdapter $parent): bool
                {
                    //$this->app->enqueueMessage('Successful updated.');

                    return true;
                }

                public function uninstall(InstallerAdapter $parent): bool
                {
                    //$this->app->enqueueMessage('Successful uninstalled.');

                    $this->deleteFiles[] = '/administrator/language/' . $this->languagePack . '/com_j2store.ini';
                    $this->deleteFiles[] = '/administrator/language/' . $this->languagePack . '/com_j2store.sys.ini';
                    $this->deleteFiles[] = '/language/' . $this->languagePack . '/com_j2store.ini';

                    $this->removeFiles();

                    return true;
                }

                public function postflight(string $type, InstallerAdapter $parent): bool
                {
                    //$this->app->enqueueMessage('Successful postflight.');

                    return true;
                }

                private function removeFiles()
                {
                    if (empty($this->deleteFiles)) {
                        return;
                    }

                    foreach ($this->deleteFiles as $file) {
                        try {
                            File::delete(JPATH_ROOT . $file);
                        } catch (FilesystemException $e) {
                            echo Text::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $file) . '<br>';
                        }
                    }
                }
            }
        );
    }
};
