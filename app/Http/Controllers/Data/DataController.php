<?php

namespace App\Http\Controllers\Data;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Item;

class DataController extends Controller
{
    public function index()
    { 
         
        $customers = json_decode(file_get_contents(base_path()."/public/example-study/example-data/customers.json"));
        $orders = json_decode(file_get_contents(base_path()."/public/example-study/example-data/orders.json"));
        $products = json_decode(file_get_contents(base_path()."/public/example-study/example-data/products.json"));
        
        foreach($customers as $key=>$value)
        {
            Customer::insertGetId([
                'name'=>$value->name,
                'since'=>$value->since,
                'revenue'=>$value->revenue
            ]);
        }
        foreach($orders as $key=>$value){
             $order_id = Order::insertGetId([
                'customer_id'=>$value->customerId,
                'total'=>$value->total
            ]);
            $item_ids = [];
            foreach($value->items as $k=>$v){
               
                $item_id = Item::insertGetId([
                    'order_id'=>$order_id,
                    'product_id'=>$v->productId,
                    'quantity'=>$v->quantity,
                    'unit_price'=>$v->unitPrice,
                    'total'=>$v->total
                ]);
                array_push($item_ids,$item_id);
            }

            Order::where('order_id',$order_id)->update([
                'item_ids'=>implode(',',$item_ids)
            ]);
           
        }

        foreach($products as $key=>$value)
        {
            Product::insert([
                'product_name'=>$value->name ?? $value->description,
                'category_id'=>$value->category,
                'price'=>$value->price,
                'stok'=>$value->stock
            ]);
        }
  

    }
}
