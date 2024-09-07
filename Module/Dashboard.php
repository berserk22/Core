<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Module;

use DI\DependencyException;
use DI\NotFoundException;
use Modules\View\ViewManager;

abstract class Dashboard extends Controller {

    /**
     * @var string
     */
    public string $upload = "uploads";

    /**
     * @var array|string[]
     */
    public array $chars = [
        'ö', 'ä', 'ü', 'ß', 'Ö', 'Ä', 'Ü', '&', '/', '  ', ' ', '_', '#', '.', '---', ':'
    ];

    /**
     * @var array|string[]
     */
    public array $replaceChars = [
        'oe', 'ae', 'ue', 'ss', 'Oe', 'Ae', 'Ue', '', '', '-' , '-', '-', '-', '-', '-', ''
    ];

    /**
     * @return ViewManager|null
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getView(): ?ViewManager {
        if ($this->getContainer()->has('ViewManager::DashboardView')){
            return $this->getContainer()->get('ViewManager::DashboardView');
        }
        else {
            return null;
        }
    }

    /**
     * @param string $title
     * @return string
     */
    public function changeChars(string $title = ''): string {
        $title = str_replace($this->chars, $this->replaceChars, $title);
        return strtolower($title);
    }

    /**
     * @return string
     */
    public function getWebPath(): string {
        @mkdir(WEB_ROOT_DIR."/".$this->upload."/".date("Y"));
        @mkdir(WEB_ROOT_DIR."/".$this->upload."/".date("Y")."/".date("m"));
        return "/".$this->upload."/".date("Y")."/".date("m")."/";
    }
}
