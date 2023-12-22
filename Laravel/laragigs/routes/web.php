<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

//Models
use App\Listing;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

$router->get('/get', function () {
    return response("<h1>Hello1</h1>", 201)
        ->header('Content-Type', 'text/plain')
        ->header('foo', 'bar');
});

$router->get('/post/{id}', function ($id) {
    // dd($id); -> Debug method (Die-Dump)
    return response('Post ' . $id);
})->where('id', '[0-9]+'); //Example: Route with Constraints

//Request class demo
$router->get('/search', function (Request $request) {
    dd($request);
});

//Send JSON object
$router->get('/json', function (Request $request) {
    return response()->json([
        'posts'=> [
            'title'=>'post1',
            'content'=>'demo content'
        ]
    ]);
});

//Bring in Models
$router->get('/model', function () {
    return Listing::all();
});

$router->get('/model/{id}', function ($id) {
    return Listing::find($id);
});

//Bring in Controllers
// $router->get('/controllers', 'ListingController@demo');

$router->get("/", function () {
    return view('listings', [
        "heading"=> "Latest Listings",
        "listings"=> [
            [
                "id"=> 1,
                "title"=> "list1",
                "description"=> "124"
            ],
            [
                "id"=> 2,
                "title"=> "list2",
                "description"=> "sdf 124"
            ]
        ]
    ]);
});
