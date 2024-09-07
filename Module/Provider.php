<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Module;

use Core\Traits\App;

class Provider {

    use App;

    /**
     * @return string
     */
    public function getName(): string {
        return get_class($this);
    }

    /**
     * @return void
     */
    // first event before boot and registry bundle
    public function beforeInit(): void {}

    /**
     * @return void
     */
    public function init(): void {}

    /**
     * @return void
     */
    public function afterInit(): void {}

    /**
     * @return void
     */
    // next event after init event's
    public function beforeBoot(): void {}

    /**
     * @return void
     */
    public function boot(): void {}

    /**
     * @return void
     */
    public function afterBoot(): void {}

    /**
     * @return void
     */
    // finale event for module
    public function register(): void {}

    /**
     * @return array [Command]
     */
    public function console():array {
        return [];
    }
}
