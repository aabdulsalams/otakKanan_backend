<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\MyBooking;
use App\Models\Users;
use App\Models\CategoryPrice;
use App\Models\MyOffice;
use JWTAuth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class MyBookingController extends Controller
{
    //user
    public function pendingList() 
    {
        $user = JWTAuth::parseToken()->authenticate();

        $bookingList1 = DB::table('my_booking')
        ->where('user_id', 'like', $user->id)
        ->where('status', 'like', 'pending')
        ->first();

        if (empty($bookingList1)) {
            return response()->json([ 'status' => "Data Not Found"]); 
        }

        $bookingList = DB::table('my_booking')
        ->where('user_id', 'like', $user->id)
        ->where('status', 'like', 'pending')
        ->get();

        return response()->json(compact('bookingList')); 

    }

    //user
    public function approvedList() 
    {
        $user = JWTAuth::parseToken()->authenticate();

        $historyBooking1 = DB::table('my_booking')
        ->where('user_id', 'like', $user->id)
        ->where('status', 'like', 'approved')
        ->first();

        if (empty($historyBooking1)) {
            return response()->json([ 'status' => "Data Not Found"]); 
        }

        $historyBooking = DB::table('my_booking')
        ->where('user_id', 'like', $user->id)
        ->where('status', 'like', 'approved')
        ->get();

        return response()->json(compact('historyBooking')); 
    }

     //user
     public function declinedList() 
     {
         $user = JWTAuth::parseToken()->authenticate();
 
         $historyBooking1 = DB::table('my_booking')
         ->where('user_id', 'like', $user->id)
         ->where('status', 'like', 'declined')
         ->first();
 
         if (empty($historyBooking1)) {
             return response()->json([ 'status' => "Data Not Found"]); 
         }
 
         $historyBooking = DB::table('my_booking')
         ->where('user_id', 'like', $user->id)
         ->where('status', 'like', 'declined')
         ->get();
 
         return response()->json(compact('historyBooking')); 
     }

    //user
    public function show($id)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $my_booking = DB::table('my_booking')
        ->where('id', '=', $id)
        ->where('user_id', '=', $user->id)
        ->where('status', '=', 'approved')
        ->first();

        if (empty($my_booking)) {
            return response()->json([ 'status' => "Data Not Found"]); 
        }

        $room = Room::find($my_booking->room_id);

        if (empty($room)) {
            return response()->json([ 'status' => "Data Not Found"]); 
        }

        $my_office =  DB::table('my_office')
        ->where('room_id', '=', $room->id)
        ->first();

        if (empty($my_office)) {
            return response()->json([ 'status' => "Data Not Found"]); 
        }

        $unit_price = CategoryPrice::find($my_booking->category_price_id);

        $owner = Users::find($my_office->user_id);


        if (empty($owner)) {
            return response()->json([ 'status' => "Data Not Found"]); 
        }

        $detail_booking['customer'] = $user;
        $detail_booking['owner'] = $owner;
        $detail_booking['room'] = $room;
        $detail_booking['unit_price'] = $unit_price;
        $detail_booking['booking_info'] = $my_booking;
        
        return response()->json(compact('detail_booking')); 

    }

    //user
    public function store(Request $request) 
    {

        $user = JWTAuth::parseToken()->authenticate();

        $validator = Validator::make($request->all(), [
            'room_id' => 'required|integer',
            'category_price_id' => 'required|integer',
            'starting_date' => 'required|date',
            'starting_time' => 'required',
            'quantity' => 'required|integer'
        ]);

        if($validator->fails()){
            return response()->json(['status' => $validator->errors()->toJson()], 400);
        }

        $quantity = $request->get('quantity');

        $category_price_id = $request->get('category_price_id');

        $category_price = CategoryPrice::find($category_price_id);

        $total_price = $category_price->price * $quantity;

        $my_booking = MyBooking::create([
            'user_id' => $user->id,
            'room_id' => $request->get('room_id'),
            'category_price_id' => $category_price_id,
            'starting_date' => $request->get('starting_date'),
            'starting_time'=>$request->get('starting_time'),
            'quantity' => $quantity,
            'total_price' => $total_price,
            'status'=>'pending',
        ]);

       
        $status = "create is success";

        return response()->json(compact('my_booking', 'status'));
    }

    //owner
    public function destroy($id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        
        $my_booking = DB::table('my_booking')
        ->where('user_id', '=', $user->id)
        ->where('id', '=', $id)
        ->first();

        if (empty($my_booking)) {
            return response()->json([ 'status' => "Data Not Found"]); 
        }

        $my_booking->delete();

        return response()->json([ 'status' => "Delete Success"]); 
    }

    //owner
    public function changeStatus(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $my_booking_temp = DB::table('my_booking')
        ->where('id', '=', $id)
        ->first();

        $my_office_temp = DB::table('my_office')
        ->where('room_id', '=', $my_booking_temp->room_id)
        ->where('user_id', '=', $user->id)
        ->first();

        if(empty($my_office_temp)) {
            return response()->json([ 'status' => "Data doesn't exist"]); 
        } else {
            $my_booking = MyBooking::find($my_booking_temp->id);

            if (empty($my_booking)) {
                
                return response()->json([ 'status' => "Data doesn't exist"]); 

            } 

            $status = $request->get('status');
            
            if($status==NULL){

                $status = $my_booking->status;

            } else { 

                $validator = Validator::make($request->all(), [
                    'status' => 'required|string|max:255'
                ]);

                if($validator->fails()){
                    return response()->json(['status' => $validator->errors()->toJson()], 400);
                }
                
                $my_booking->update([
                    'status' => $status
                ]);
                
            }

            

            return response()->json([ 'status' => "Update successfully"]);
        }
    }

    //owner
    public function bookedRoom() 
    {
        $user = JWTAuth::parseToken()->authenticate();
        $owner_booked = array();

        $my_office = DB::table('my_office')
        ->where('user_id', 'like', $user->id)
        ->get();

        foreach ($my_office as $key) {
            $my_booking = DB::table('my_booking')
            ->where('room_id', 'like', $key->room_id)
            ->get();

            foreach ($my_booking as $key2) {
                array_push($owner_booked, $key2);
            }

        }

        return response()->json(compact('owner_booked'));

    }

}
