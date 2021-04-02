<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\CommonRegulations;
use App\Models\Facility;
use App\Models\FoodDrinks;
use App\Models\Gallery;
use App\Models\OperationalTimes;
use App\Models\RoomCategoryPrice;
use App\Models\RoomFunction;
use App\Models\RoomType;
use App\Models\Users;
use App\Models\CategoryPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use JWTAuth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    
    public function index()
    {
        $rooms = Room::all();

        return response()->json(compact('rooms'));
    }

    public function terUpdate() {

        $rooms = DB::table('rooms')
        ->orderBy('updated_at', 'desc')
        ->take(4)
        ->get();

        return response()->json(compact('rooms'));
    }

    
    public function show($id)
    {  
        $room = Room::find($id);
        
        if (empty($room)) {
            return response()->json([ 'status' => "Data Not Found"]); 
        }

        $roomType = DB::table('room_types')
        ->where('room_id', 'like', $room->id)
        ->get();

        $roomFunction = DB::table('room_functions')
        ->where('room_id', 'like', $room->id)
        ->get();

        $gallery = DB::table('galleries')
        ->where('room_id', 'like', $room->id)
        ->get();

        $facility = DB::table('facilities')
        ->where('room_id', 'like', $room->id)
        ->get();

        $common_regulations = DB::table('common_regulations')
        ->where('room_id', 'like', $room->id)
        ->get();

        $operational_times = DB::table('operational_times')
        ->where('room_id', 'like', $room->id)
        ->get();

        $room_category_price = DB::table('room_category_price')
        ->where('room_id', 'like', $room->id)
        ->get();

        $category_price_temp = array();
        foreach ($room_category_price as $key) {
            $user_id_temp = $key->user_id;
            $category_price = DB::table('category_price')
            ->where('id', 'like', $key->category_price_id)
            ->first();
            array_push($category_price_temp, $category_price);
        }
        
        $user = DB::table('users')
        ->where('id', 'like', $user_id_temp)
        ->first();

        $detail_room['id'] = $room->id;
        $detail_room['name'] = $room->name;
        $detail_room['address'] = $room->address;
        $detail_room['description'] = $room->description;
        $detail_room['latitude'] = $room->latitude;
        $detail_room['longitude'] = $room->longitude;
        $detail_room['room_type'] = array();

        foreach ($roomType as $key) {
            $room_type_temp['name'] = $key->name;
            $room_type_temp['capacity'] = $key->capacity;
            $room_type_temp['layout'] = $key->layout;
            array_push($detail_room['room_type'], $room_type_temp);
        }

        $detail_room['room_function'] = array();

        foreach ($roomFunction as $key) {
            $room_function_temp['name'] = $key->name;
            array_push($detail_room['room_function'], $room_function_temp);
        }

        $detail_room['gallery'] = array();

        foreach ($gallery as $key) {
            $gallery_temp['filename'] = $key->filename;
            array_push($detail_room['gallery'], $gallery_temp);
        }

        $detail_room['facility'] = array();

        foreach ($facility as $key) {
            $facility_temp['name'] = $key->name;
            $facility_temp['status'] = $key->status;
            array_push($detail_room['facility'], $facility_temp);
        }

        $detail_room['common_regulations'] = array();

        foreach ($common_regulations as $key) {
            $common_regulations_temp['name'] = $key->name;
            array_push($detail_room['common_regulations'], $common_regulations_temp);
        }

        $detail_room['operational_times'] = array();

        foreach ($operational_times as $key) {
            $operational_times_temp['day'] = $key->day;
            $operational_times_temp['open_times'] = $key->open_times;
            $operational_times_temp['close_times'] = $key->close_times;
            array_push($detail_room['operational_times'], $operational_times_temp);
        }

        $detail_room['category_price'] = array();

        foreach ($category_price_temp as $key) {
            $temp['id'] = $key->id;
            $temp['name'] = $key->name;
            $temp['price'] = $key->price;
            array_push($detail_room['category_price'], $temp);
        }

        $detail_room['created_by'] = $user->name;

        return response()->json(compact('detail_room'));

    }
    
    
}
