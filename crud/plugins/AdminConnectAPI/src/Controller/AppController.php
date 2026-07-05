<?php
declare(strict_types=1);

namespace AdminConnectAPI\Controller;

use AdminConnectAPI\Service\ApiClient;
use App\Controller\AppController as BaseController;

class AppController extends BaseController
{
    protected ApiClient $api;

    public function initialize(): void
    {
        parent::initialize();
        $this->api = new ApiClient();
    }
}
