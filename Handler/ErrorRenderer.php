<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Core\Handler;

use Core\Traits\App;
use DI\DependencyException;
use DI\NotFoundException;
use Modules\View\ViewManager;
use Slim\Interfaces\ErrorRendererInterface;
use Throwable;

class ErrorRenderer implements ErrorRendererInterface {

    use App;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __invoke(Throwable $exception, bool $displayErrorDetails): string {
        if ($this->getContainer()->has('ViewManager::View')){
            /** @var ViewManager $view */
            $view = $this->getContainer()->get('ViewManager::View');
            $view->setVariables([
                'line'=>$exception->getLine(),
                'file'=>$exception->getFile(),
                'code'=>$exception->getCode(),
                'message'=>$exception->getMessage(),
                'trace'=>$exception->getTrace(),
                'traceAsString'=>$exception->getTraceAsString()
            ]);

            return match ($exception->getCode()) {
                400 => $view->getHtml('error/400'),
                403 => $view->getHtml('error/403'),
                404 => $view->getHtml('error/404'),
                405 => $view->getHtml('error/405'),
                500 => $view->getHtml('error/500'),
                501 => $view->getHtml('error/501'),
                default => $displayErrorDetails ? $view->getHtml('error/details') : $view->getHtml('error/short'),
            };
        }
        else {
            if (property_exists($exception, 'xdebug_message')) {
                return "<pre>" . $exception->xdebug_message . "</pre>";
            }
            else {
                return "<pre>" . json_encode($exception) . "</pre>";
            }
        }
    }
}
