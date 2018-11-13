<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\uploader\Installers;

use rollun\installer\Install\InstallerAbstract;
use rollun\uploader\Callback\Factory\UploaderAbstractFactory;

class UploaderInstaller extends InstallerAbstract
{
    /**
     * install
     * @return array
     */
    public function install()
    {
        return [
            'dependencies' => [
                'abstract_factories' => [
                    UploaderAbstractFactory::class,
                ],
            ],
        ];
    }

    /**
     * Clean all installation
     * @return void
     */
    public function uninstall()
    {
    }

    /**
     * Return string with description of installable functional.
     * @param string $lang ; set select language for description getted.
     * @return string
     */
    public function getDescription($lang = "en")
    {
        switch ($lang) {
            case "ru":
                $description = "Позволяет загружать данные в DataStore";
                break;
            default:
                $description = "Does not exist.";
        }

        return $description;
    }

    public function isInstall()
    {
        $config = $this->container->get('config');

        return (isset($config['dependencies']['abstract_factories'])
            && in_array(UploaderAbstractFactory::class, $config['dependencies']['abstract_factories']));
    }
}
