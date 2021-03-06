<?php

namespace Yasser\LaravelDashboard\Controllers;

use Illuminate\Http\File;
use Yasser\LaravelDashboard\Events\NotificationEvent;
use Yasser\LaravelDashboard\Notifications\DashboardNotification;
use App\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class LaravelSettingsController extends Controller
{

    /** @var array  */

    private $user_information = ['Country','Zip','Address','Description','City','LastName'];

    /** @array */

    private $user_register_default_information = ['email','name'];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['web', 'auth']);
    }

    //

    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */

     public function index(){

        return view('LaravelDashboard::settings');

     }

    /**
     * Update the specified resource in storage information.
     * @param Request $request
     * @return Response
     */

     public function Update(Request $request){

         $this->Validator($request->all())->validate();

         $attach = User::find(auth()->id())->information();

         is_null($attach->first()) ? $attach->create($this->Filter($this->user_information, $request->all())) : $attach->Update($this->Filter($this->user_information, $request->all()));

         $user = User::find(auth()->id())->update($this->Filter($this->user_register_default_information, $request->all()));

         User::find(auth()->id())->notify((new DashboardNotification('your account has been updated successfully', 'settings', \auth()->user()->name))->delay(now()->addSeconds(40)));

         event(new NotificationEvent(['message' => 'your information has benn updated successfully', 'type' => 'settings', 'name' => auth()->user()->name, 'to' => 'auth']));

         return redirect()->route('dashboard.settings.update');

     }


    /**
     * Get a validator for updating user account.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */

     public function Validator(array $data){

        return Validator::make($data,[
             'name' => ['required', 'string', 'max:255'],
             'email' => ['required', 'string', 'email', 'max:255'],
             'LastName' => ['required','string','max:20'],
             'Address'  => ['required','string','min:3'],
             'City' => ['required','string'],
             'Zip' => ['required','integer'],
             'Country' => ['required','string'],
             'Description' => ['required','string','min:80','max:200']
         ]);

     }

    /**
     * Filter Data
     * @param array $filter
     * @param array $request
     * @return array
     */

     public function Filter(array $filter,array $request){

         return array_filter($request,function ($element) use($filter){

              return in_array($element,$filter);

         },ARRAY_FILTER_USE_KEY);

     }

     /**
      * Delete Account Render
      *
      * @return Renderable
      */

      public function RenderDelete(){

          return view("LaravelDashboard::dashboard.delete");

      }


      /**
       * Upload Image
       * @param Request $request
       * @return RedirectResponse
       */

       public function Upload(Request $request){

           /** @var  $user */

           $user = User::find(auth()->id())->attachementUser();

           /** @var  $generate_name * */

           $generate_name = str_random(16) . '.' . $request->file('file')->getClientOriginalExtension();

           /** @var  $upload_avatar */

           $upload_avatar = $user->create([
               'file_name'=>$generate_name,
            ]);

           if($upload_avatar){

              $user->getRelated()->newInstance()
                  ->UploadFile(new File($request->file('file')),$generate_name);

           }

          return redirect()->route('dashboard.settings.index');

       }

}
