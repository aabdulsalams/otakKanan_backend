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
use App\Models\MyOffice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use JWTAuth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Http\Request;

class MyOfficeController extends Controller
{
    public function index() {
        $user = JWTAuth::parseToken()->authenticate();
        
        $rooms = DB::table('my_office')
        ->where('user_id', 'like', $user->id)
        ->get();

        $my_office = array();

        foreach ($rooms as $key) {
            $temp = Room::find($key->room_id);

            array_push($my_office, $temp);
        }

        return response()->json(compact('my_office')); 

    }

    public function show($id)
    {  
        $user = JWTAuth::parseToken()->authenticate();
        
        $room = DB::table('rooms')
        ->where('user_id', 'like', $user->id)
        ->where('id', '=', $id)
        ->first();
        
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

    public function store(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'address' => 'required',
            'description' => 'required',
            'latitude' => 'required',
            'longitude'=> 'required',
            'capacity' => 'required',
            'layout' => 'required|image|mimes:png,jpeg,jpg',
            'room_type_name' => 'required',
            'room_function_name' => 'required',
            'filename' => 'required|image|mimes:png,jpeg,jpg',
            'facility_name' => 'required|max:255',
            'facility_status' => 'required|max:255',
            'regulation_name' => 'required|max:255',
            'day' => 'required',
            'open_times' => 'required',
            'close_times' => 'required',
            'category_price_name' => 'required',
            'price' => 'required'
        ]);

        if($validator->fails()){
            return response()->json(['status' => $validator->errors()->toJson()], 400);
        }

        if($request->hasFile('filename')) {
            
            $validator = Validator::make($request->all(), [
                'filename' => 'required|image|mimes:png,jpeg,jpg'
            ]);

            if($validator->fails()){
                return response()->json(['status' => $validator->errors()->toJson()], 400);
            }

            $file = $request->file('filename');
            $filename = 'otakkanan/gallery/' . $user->name . '/' . 'rm-' . time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/', $filename);

        }
        
            $room = Room::create([
                'user_id' => $user->id,
                'name' => $request->get('name'),
                'filename' => $filename,
                'address' => $request->get('address'),
                'description' => $request->get('description'),
                'latitude' => $request->get('latitude'),
                'longitude' => $request->get('longitude'),
            ]);
        
            if($request->hasFile('layout')) {

                $validator = Validator::make($request->all(), [
                    'layout' => 'required|image|mimes:png,jpeg,jpg'
                ]);
    
                if($validator->fails()){
                    return response()->json(['status' => $validator->errors()->toJson()], 400);
                }

                $file = $request->file('layout');
                $layout = 'otakkanan/gallery/' . $user->name . '/' . 'ly-' . time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/', $layout);
            }
        
            $roomType = RoomType::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'name' => $request->get('room_type_name'),
                'capacity' => $request->get('capacity'),
                'layout' => $layout
            ]);
        
            $roomFunction = RoomFunction::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'name' => $request->get('room_function_name')
            ]);
        
            
        
            $gallery = Gallery::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'filename' => $filename
            ]);
        
            $facility = Facility::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'name' => $request->get('facility_name'),
                'status' => $request->get('facility_status')
            ]);
        
            $common_regulations = CommonRegulations::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'name' => $request->get('regulation_name')
            ]);
       
            $operational_times = OperationalTimes::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'day' => $request->get('day'),
                'open_times' => $request->get('open_times'),
                'close_times' => $request->get('close_times')
            ]);
        
            $category_price = CategoryPrice::create([
                'user_id' => $user->id,
                'name' => $request->get('category_price_name'),
                'price' => $request->get('price')
            ]);
        
            $room_category_price = RoomCategoryPrice::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'category_price_id' => $category_price->id
            ]);
        
            $my_office = MyOffice::create([
                'room_id' => $room->id,
                'user_id' => $user->id
            ]);

        
        return response()->json(compact('room', 'roomType', 'roomFunction', 'gallery', 'facility', 'common_regulations', 'operational_times', 'category_price', 'room_category_price', 'my_office'));

    }

    public function update() {

        $user = JWTAuth::parseToken()->authenticate();

        $room_id = $request->get('room_id');

        $my_office = DB::table('my_office')
        ->where('user_id', '=', $user->id)
        ->where('room_id', '=', $room_id)
        -first();

        $room = Room::find($my_office->room_id);

        if (empty($room)) {
            return response()->json([ 'status' => "Data Not Found"]); 
        }

        $name = $request->get('name');
        $address = $request->get('address');
        $description = $request->get('description');
        $latitude = $request->get('latitude');
        $longitude = $request->get('longitude');


        $room->update([
            'user_id' => $user->id,
            'name' => $name,
            'address' => $address,
            'description' => $description,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        return response()->json([ 'status' => "Update Success"]);

    }

    public function destroy($id)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $my_office = DB::table('my_office')
        ->where('user_id', '=', $user->id)
        ->where('room_id', '=', $id)
        ->first();

        $room = Room::find($my_office->room_id);

        if (empty($room)) {
            return response()->json([ 'status' => "Data Not Found"]); 
        }

        $room->delete();

        return response()->json([ 'status' => "Delete Success"]); 
             
    }

}
