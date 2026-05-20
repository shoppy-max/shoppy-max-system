<?php

namespace Tests\Feature;

use App\Http\Controllers\DirectResellerController;
use App\Http\Controllers\DirectResellerDuesController;
use App\Http\Controllers\DirectResellerPaymentController;
use App\Http\Controllers\ResellerController;
use App\Http\Controllers\ResellerDuesController;
use App\Http\Controllers\ResellerPaymentController;
use App\Http\Controllers\ResellerTargetController;
use Illuminate\Http\Request;
use Tests\TestCase;

class OrderRoutingTest extends TestCase
{
    public function test_packing_index_route_is_not_captured_by_order_show_wildcard(): void
    {
        $request = Request::create('/orders/packing', 'GET');

        $route = app('router')->getRoutes()->match($request);

        $this->assertSame('orders.packing.index', $route->getName());
    }

    public function test_dispatched_packing_routes_are_not_captured_by_order_show_wildcard(): void
    {
        $getRoute = app('router')->getRoutes()->match(Request::create('/orders/packing/dispatched', 'GET'));
        $postRoute = app('router')->getRoutes()->match(Request::create('/orders/packing/123/mark-delivered', 'POST'));

        $this->assertSame('orders.packing.dispatched', $getRoute->getName());
        $this->assertSame('orders.packing.mark-delivered', $postRoute->getName());
    }

    public function test_reseller_named_routes_generate_matching_user_facing_paths(): void
    {
        $this->assertSame('/direct-resellers', route('resellers.index', [], false));
        $this->assertSame('/resellers', route('direct-resellers.index', [], false));

        $this->assertSame('/direct-reseller-payments', route('reseller-payments.index', [], false));
        $this->assertSame('/reseller-payments', route('direct-reseller-payments.index', [], false));

        $this->assertSame('/direct-reseller-dues', route('reseller-dues.index', [], false));
        $this->assertSame('/reseller-dues', route('direct-reseller-dues.index', [], false));

        $this->assertSame('/direct-reseller-targets', route('reseller-targets.index', [], false));
    }

    public function test_reseller_public_paths_resolve_to_the_expected_data_workflows(): void
    {
        $this->assertRouteMatches('/direct-resellers', 'GET', 'resellers.index', ResellerController::class.'@index');
        $this->assertRouteMatches('/direct-resellers/create', 'GET', 'resellers.create', ResellerController::class.'@create');
        $this->assertRouteMatches('/direct-resellers', 'POST', 'resellers.store', ResellerController::class.'@store');

        $this->assertRouteMatches('/resellers', 'GET', 'direct-resellers.index', DirectResellerController::class.'@index');
        $this->assertRouteMatches('/resellers/create', 'GET', 'direct-resellers.create', DirectResellerController::class.'@create');
        $this->assertRouteMatches('/resellers', 'POST', 'direct-resellers.store', DirectResellerController::class.'@store');

        $this->assertRouteMatches('/direct-reseller-payments', 'GET', 'reseller-payments.index', ResellerPaymentController::class.'@index');
        $this->assertRouteMatches('/reseller-payments', 'GET', 'direct-reseller-payments.index', DirectResellerPaymentController::class.'@index');

        $this->assertRouteMatches('/direct-reseller-dues', 'GET', 'reseller-dues.index', ResellerDuesController::class.'@index');
        $this->assertRouteMatches('/reseller-dues', 'GET', 'direct-reseller-dues.index', DirectResellerDuesController::class.'@index');

        $this->assertRouteMatches('/direct-reseller-targets', 'GET', 'reseller-targets.index', ResellerTargetController::class.'@index');
    }

    private function assertRouteMatches(string $uri, string $method, string $name, string $action): void
    {
        $request = Request::create($uri, $method);

        $route = app('router')->getRoutes()->match($request);

        $this->assertSame($name, $route->getName());
        $this->assertSame($action, ltrim($route->getActionName(), '\\'));
    }
}
