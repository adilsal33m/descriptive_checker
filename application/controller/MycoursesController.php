<?php

/**
 * This controller shows an area that's only visible for logged in users (because of Auth::checkAuthentication(); in line 16)
 */
class MycoursesController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class
     */
    public function __construct()
    {
        parent::__construct();

        // this entire controller should only be visible/usable by logged in users, so we put authentication-check here
        Auth::checkAuthentication();
    }

    /**
     * This method controls what happens when you move to /dashboard/index in your app.
     */
    public function index()
    {
        $this->View->render('mycourses/index');
    }

    public function test()
    {
        $this->View->render('mycourses/test');
    }

    public function compute()
    {
      $test = unserialize(base64_decode($_POST['questions']));
      $answers = $_POST['answers'];
      if (!Csrf::isTokenValid()) {
           LoginModel::logout();
           Redirect::home();
           exit();
       }
        Session::set("marks",Checker::check($test,$answers));
        $this->View->render('mycourses/compute');
    }
}
