<?php

namespace Tests\Feature;

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
}
