<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\MyBooking;
use App\Models\Users;
use App\Models\CategoryPrice;
use App\Models\MyOffice;
use JWTAuth;
use Illuminate\Http\Request;

class MyBookingController extends Controller
{
    //user
    public function bookingList() 
    {
        $user = JWTAuth::parseToken()->authenticate();

        $bookingList1 = DB::table('my_booking')
        ->where('user_id', 'like', $user->id)
        ->where('status', 'not like', 'finished')
        ->first();

        if (empty($bookingList1)) {
            return response()->json([ 'status' => "Data Not Found"]); 
        }

        $bookingList = DB::table('my_booking')
        ->where('user_id', 'like', $user->id)
        ->where('status', 'not like', 'finished')
        ->get();

        return response()->json(compact('bookingList')); 

    }

    //user
    public function finishedList() 
    {
        $user = JWTAuth::parseToken()->authenticate();

        $historyBooking1 = DB::table('my_booking')
        ->where('user_id', 'like', $user->id)
        ->where('status', 'like', 'finished')
        ->first();

        if (empty($historyBooking1)) {
            return response()->json([ 'status' => "Data Not Found"]); 
        }

        $historyBooking = DB::table('my_booking')
        ->where('user_id', 'like', $user->id)
        ->where('status', 'like', 'finished')
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
    public function store() 
    {

        $user = JWTAuth::parseToken()->authenticate();

        $validator = Validator::make($request->all(), [
            'room_id' => 'required|integer',
            'category_price_id' => 'required|integer',
            'starting_date' => 'required|date',
            'starting_time' => 'required|time',
            'quantity' => 'required|integer',
            'total_price' => 'required|integer',
            'status' => 'required|string'
        ]);

        if($validator->fails()){
            return response()->json(['status' => $validator->errors()->toJson()], 400);
        }

        $quantity = $request->get('quantity');

        $category_price_id = $request->get('category_price_id');

        $category_price = CategoryPrice::find($category_price_id);

        $total_price = $category_price->price * $quantity;

        $my_booking = MyBooking::create([
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
    public function changeStatus($id)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $my_booking_temp = DB::table('my_booking')
        ->where('user_id', '=', $user->id)
        ->where('id', '=', $id)
        ->first();

        $my_booking = MyBooking::find($my_booking_temp->id);

        if (empty($my_booking)) {
            
            return response()->json([ 'status' => "Data doesn't exist"]); 

        } 

        $status = $request->get('status');
        
        if($status==NULL){

            $status = $my_booking->status;

        } else{

            $validator = Validator::make($request->all(), [
                'status' => 'required|string|max:255'
            ]);

            if($validator->fails()){
                return response()->json(['status' => $validator->errors()->toJson()], 400);
            }
            if ($status = "declined"){
                $status = "I'm Sorry, room already full";
            } else if ($status = "approved") {
                $status = "You can print the invoice, then show me on the office";
            }

        }

        $my_booking->update([
            'status' => $status
        ]);

        return response()->json([ 'status' => "Update successfully"]);
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