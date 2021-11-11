<?php

namespace APP\Abase\Controller;

use Exception;
use Illuminate\Container\Container as Application;
use Illuminate\Routing\Controller;
use APP\Abase\Service\ServiceInterface;

/**
 * Class BaseController
 * @package APP\Abase\Controller
 * @author Jade <zhengyiunity@gmail.com>
 */
abstract class BaseController extends Controller
{

    /**
     * @var Application
     */
    protected $app;

    protected $service;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->makeService();
    }

    abstract protected function service();


    /**
     * @return Service
     * @throws RepositoryException
     */
    protected function makeService()
    {
        $service = $this->app->make($this->service());
        if (!$service instanceof ServiceInterface) {
            throw new Exception("Class {$this->service()} must be an instance of APP\Abase\Service\ServiceInterface");
        }

        return $this->service = $service;
    }
}
