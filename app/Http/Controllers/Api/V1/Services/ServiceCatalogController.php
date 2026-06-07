<?php

namespace App\Http\Controllers\Api\V1\Services;

use App\Http\Controllers\Controller;
use App\Services\ServiceCatalogService;
use App\Services\SessionContextService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ServiceCatalogController extends Controller
{
    public function __construct(
        protected SessionContextService $sessionContext,
        protected ServiceCatalogService $serviceCatalog
    ) {}

    public function index(): JsonResponse
    {
        $session = $this->sessionContext->current();

        if (! $session) {
            return ApiResponse::error(
                'SESSION_EXPIRED',
                'Session is not active',
                401
            );
        }

        $session->load('terminalDevice');

        return ApiResponse::success([
            'services' => $this->serviceCatalog->forBranch($session->terminalDevice->branch_id),
        ]);
    }
}
