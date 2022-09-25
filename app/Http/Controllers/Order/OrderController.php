<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Item;
use App\Models\Product;
use App\Models\Customer;

class OrderController extends Controller
{
     
    public function index()
    {
        $orders = Order::with('items')->get();
        return response()->json($orders);
    }

    public function store(Request $request)
    {  

        $total_price =  $this->total_payment_calc($request->all());
        $customer = Customer::where('customer_id',$request['customerId'])->first();
        //Eğer stoktaki ürün sayısı yeterli değilse bilgi ver ve sipariş oluşturma.
        $stock_control = $this->is_empty_stock($request->all());
        if (is_array($stock_control)) {
            $error_message = implode(',', $stock_control). " id'li ürünlerin stok sayıları yeterli değil.";
            $json_data = [
                'status'=>false,
                'message'=>$error_message
            ];
            return response()->json($json_data);
        } 

        //Eğer müşterinin bakiyesi yetmiyorsa hata döndürüyoruz.
        if ($customer->revenue < $total_price) {
            return response()->json([
                'status'=>false,
                'message'=>"Müşteri bakiyesi yeterli değil."
            ]);
        }



        //Sipariş edilen ürün sayısını stoktan düşüyoruz
        foreach($request->items as $k=>$v)
        {      

            $product = Product::where('product_id',$v['productId'])->first();
            //İD 2 ise indirim yapıyoruz.
            $total_price =  $this->category_discount_calculate($product->category_id,$v['quantity'],$total_price);
            $total_price = $total_price['total_price'];
            $product->stok = $product->stok - $v['quantity'];
            $product->save();


            if ($k == array_key_last($v)) {
                $product = Product::orderBy('price','DESC')->first();
                $product->price = $this->get_product_price();
                $product->save();
            }
        }

        
        $order_id = Order::insertGetId([
            'customer_id'=>$request->customerId,
            'total'=>$this->calculate_discount($total_price)
        ]);
        $item_ids = $this->get_item_ids($request->all(),$order_id);

        Order::where('order_id',$order_id)->update([
            'item_ids'=>$item_ids
        ]);



        //İşlem gerçekleştikten sonra müşterinin bakiyesini düzenliyoruz.
        Customer::where('customer_id',$request->customerId)->update([
            'revenue'=> $customer->revenue-$total_price
        ]);

        //geriye başarılı olduğunu gösteren json döndürüyoruz
        return response()->json([
            'status'=>true,
            'statusCode'=>200,
            'message'=>'Sipariş başarıyla oluşturuldu.'
        ]);

         
    } 


    //Ürünlerin toplan fiyatını buluyor
    public function total_payment_calc($order_detail)
    { 
        $total_price = 0;
        foreach($order_detail['items'] as $key=>$value){
            $total_price += $value['quantity'] * $value['unitPrice'];
        }
        return $total_price; 
    }

    public function get_item_ids($order_detail,$order_id)
    {

        $item_ids = [];
        foreach($order_detail['items'] as $key=>$value)
        {
            $item_id = Item::insertGetId([
                'order_id'=>$order_id,
                'product_id'=>$value['productId'],
                'quantity'=>$value['quantity'],
                'unit_price'=>$value['unitPrice'],
                'total'=>$value['total']
            ]);
            array_push($item_ids,$item_id);
        }

        return implode(',',$item_ids);
    }
    public function is_empty_stock($order_detail)
    {
        $errors = [];
        foreach($order_detail['items'] as $key=>$value)
        { 
            $product = Product::where('product_id',$value['productId'])->first();
            if ($product->stok < $value['quantity']) {
                array_push($errors,$value['productId']);
            }
             
        }
        if (empty($errors)) {
            return true;
        }else{
            return $errors;
        } 
    }


    public function calculate_discount($total_price)
    {
        if ($total_price > 1000) {
           $total_price = $total_price - ($total_price / 10);
        }

        return $total_price;
    }

    public function delete($id)
    {

        $order_detail = Order::where('order_id',$id)->with('items')->first();
        
        
        //iptal edilen siparişteki ürünleri stoğa geri ekliyoruz
        //ve item tablosundan siliyoruz
        foreach($order_detail->items as $key)
        { 
            $product = Product::where('product_id',$key['product_id'])->first();
            $product->stok = $product->stok + $key['quantity'];
            $product->save();

            Item::where('item_id',$key['item_id'])->delete();

            //Siparişi veren müşterinin bakiyesine iptal edilen siparişin tutarını ekleyeceğiz.
            $customer = Customer::where('customer_id',$order_detail->customer_id)->first();
            
            $customer->revenue = $customer->revenue + $key['total'];
            $customer->save();


        }
        //Son olarak sipariş tablosundan siparişi siliyoruz.
        Order::where('order_id',$id)->delete();

        //json sonuç döndürüyoruz
        return response()->json([
            'status'=>true,
            'statusCode'=>200,
            'message'=>'Sipariş başarıyla silindi.',
            'siparis_id'=>$id
        ]);


    }

    public function category_discount_calculate($category_id,$total_price,$quantity = null)
    {
        $cheap_product = Product::orderBy('price','asc')->first();  
        if ($category_id== 2 && $quantity == 6) {
                $total_price = $total_price - $v->price;
            }
        if ($category_id == 1 && $quantity >=2) {
            $cheap_product->price = $cheap_product->price * (1/10);
            $cheap_product->save();
        } 
        
        return [
            'total_price'=>$total_price,
            
        ];

    }

    public function get_product_price()
    {
        $cheap_product = Product::orderBy('price','asc')->first();  
        $product_price = $cheap_product->price;
        return $product_price;
    }
}
